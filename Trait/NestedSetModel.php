<?php namespace mjolnir\database;

/**
 * Adds tree support to a model. The implementation used is that of
 * a NestedSet.
 *
 * @package    mjolnir
 * @category   Library
 * @author     Ibidem Team
 * @copyright  (c) 2013, Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
trait Trait_NestedSetModel
{
	/**
	 * The currently recognized parent key for elements.
	 *
	 * @return string key name
	 */
	static function tree_parentkey()
	{
		return 'parent';
	}

	/**
	 * @return string lft keyname
	 */
	static function tree_lft()
	{
		return 'lft';
	}

	/**
	 * @return string rgt keyname
	 */
	static function tree_rgt()
	{
		return 'rgt';
	}

	// ------------------------------------------------------------------------
	// Factory interface

	#
	# tree_checks should be invoked manually after adding your tests in your
	# check methods; tree_inserter accepts the same parameters as inserter only
	# you do not call run on it since it is a self contained function; same for
	# tree_updater. Both tree_inserter and tree_updater expect the parentkey in
	# in the field list
	#

	/**
	 * Add rules pertaining to the integrity of the nested set.
	 */
	static
	function tree_checks
		(
			\mjolnir\types\Validator $validator,
			array $fields, $context = null
		)
	{
		$prt = static::tree_parentkey();
		$validator->rule($prt, 'exists', empty($fields[$prt]) || static::exists($fields[$prt], 'id'));
	}

	/**
	 * The lft and rgt keys are automatically merged if not specified in the
	 * fields under nums. If lft and rgt are specified in the fields list an
	 * exception will be thrown to prevent accidental tree corruption.
	 */
	protected static
	function tree_inserter
		(
			array $input,
			array $strs, array $bools = null, array $nums = null
		)
	{
		$lft = static::tree_lft();
		$rgt = static::tree_rgt();
		$prt = static::tree_parentkey();

		// Guards
		// ------

		if (isset($input[$lft]) or isset($input[$rgt]))
		{
			throw new \Exception('Integrity violation: attempted to hardcode lft and rgt values though tree_inserter');
		}

		if ( ! isset($input[$prt]))
		{
			$input[$prt] = null;
		}

		// Normalize
		// ---------

		! empty($input[$prt]) or $input[$prt] = null;

		// Process Insert
		// --------------

		$fieldlist = static::fieldlist();

		if ($input[$prt] === null)
		{
			// calculate lft, rgt
			$vroot = static::tree_virtual_root();
			$input[$lft] = $vroot !== null ? $vroot['rgt'] : 1;
			$input[$rgt] = $input[$lft] + 1;

			// the insertion happens at the end; no offset operations required
			static::inserter
				(
					$input,
					$fieldlist['strs'], $fieldlist['bools'], $fieldlist['nums']
				)
				->run();
		}
		else # parent is set
		{
			$parent = static::entry($input[$prt]);
			$offsetidx = $parent[$rgt];
			$offset = 2;

			// update nodes adjacent to parent
			static::tree_offset_nodes($offsetidx, $offset);
			// update parent
			static::tree_create_node_space($input[$prt]);

			// Insert in new empty space
			// -------------------------

			$input[$lft] = $parent[$rgt];
			$input[$rgt] = $input[$lft] + 1;

			static::inserter
				(
					$input,
					$fieldlist['strs'], $fieldlist['bools'], $fieldlist['nums']
				)
				->run();
		}

		static::$last_inserted_id = \app\SQL::last_inserted_id();
		static::clear_cache();
	}

	/**
	 * Creates space at the end of the node for other nodes. Space is measured
	 * in nodes so 1 mathematically is equivalent to offsetting by 2, ie. the
	 * right and left index.
	 */
	protected static function tree_create_node_space($node_id, $space = 1)
	{
		$lft = static::tree_lft();
		$rgt = static::tree_rgt();

		$target = static::entry($node_id);

		static::statement
			(
				'
					SELECT
					@offsetidx := :offsetidx;

					UPDATE `[table]`
					   SET [rgt] = [rgt] + :rgtoffset
					 WHERE [rgt] >= @offsetidx
					   AND [lft] <  @offsetidx
				',
				[
					'[rgt]' => $rgt,
					'[lft]' => $lft
				]
			)
			->num(':rgtoffset', $space * 2)
			->num(':offsetidx', $target[$rgt])
			->run();
	}

	/**
	 * Moves nodes to the right, based on offsetidx. Node which lft or rgt
	 * correspinding to offset index is not touched.
	 */
	protected static function tree_offset_nodes($offsetidx, $offset)
	{
		static::statement
			(
				'
					UPDATE `[table]`
					   SET [lft] = [lft] + :lftoffset,
						   [rgt] = [rgt] + :rgtoffset
					 WHERE [lft] > :offsetidx
				',
				[
					'[rgt]' => static::tree_rgt(),
					'[lft]' => static::tree_lft()
				]
			)
			->num(':lftoffset', $offset)
			->num(':rgtoffset', $offset)
			->num(':offsetidx', $offsetidx)
			->run();
	}

	/**
	 * The lft and rgt keys are automatically merged if not specified in the
	 * fields under nums. If lft and rgt are specified in the fields list an
	 * exception will be thrown to prevent accidental tree corruption.
	 */
	protected static
	function tree_updater
		(
			$id,
			array $input,
			array $strs, array $bools = null, array $nums = null
		)
	{
		$lft = static::tree_lft();
		$rgt = static::tree_rgt();
		$prt = static::tree_parentkey();

		if (isset($input[$lft]) or isset($input[$rgt]))
		{
			throw new \Exception('Tree integrity violation: attempted to hardcode lft and rgt values though tree_updater');
		}

		// move account?
		if (isset($input[$prt]))
		{
			static::tree_move_process($id, $input[$prt]);
		}

		// remove lft and rgt from num input
		if ($nums !== null)
		{
			$nums = \array_diff($nums, [$lft, $rgt]);
		}

		static::updater($id, $input, $strs, $bools, $nums)->run();
		static::clear_cache();
	}

	/**
	 * Moves node into parent node. Trying to move the node into itself will
	 * result in an exception.
	 */
	protected static function tree_move_process($node_id, $new_parent)
	{
		$lft = static::tree_lft();
		$rgt = static::tree_rgt();

		$node = static::entry($node_id);

		if ($new_parent !== null)
		{
			$parent = static::entry($new_parent);

			// check for potential recusion in tree; in case it was ommited in
			// validation by mistake
			if (static::tree_node_is_child_of_parent($new_parent, $node_id))
			{
				throw new \Exception('Tried to move child node as child of itself. Recursion in tree is not supported.');
			}
		}
		else # new_parent === null (ie. root)
		{
			// virtual root can never be null in this scenario since we already
			// have a node, the one we're processing which is guranteed
			$parent = static::tree_virtual_root();
		}

		static::statement
			(
				'
				-- initialize parameters

					SELECT
					@lft := :node_lft,
					@rgt := :node_rgt,
					@prgt := :parent_rgt;

					SELECT
					@nsize := @rgt - @lft + 1, # node size
					@offset := @prgt;

				-- Step 1: remove moved nodes

					# convert to negative values to remove from tree

					UPDATE `[table]`
					   SET [lft] = 0 - ([lft]),
						   [rgt] = 0 - ([rgt])
					 WHERE [lft] >= @lft
					   AND [rgt] <= @rgt;

				-- Step 2: recycle current space

					# offset adjacent nodes

					UPDATE `[table]`
					   SET [lft] = [lft] - @nsize,
						   [rgt] = [rgt] - @nsize
					 WHERE [lft] > @rgt;

					# offset parent nodes

					UPDATE `[table]`
					   SET [rgt] = [rgt] - @nsize
					 WHERE [lft] < @lft
					   AND [rgt] > @rgt;

				-- Step 3: create new space

					SELECT
					@offset := IF(@offset > @rgt, @offset - @nsize, @offset);

					# offset adjacent nodes

					UPDATE `[table]`
					   SET [lft] = [lft] + @nsize,
						   [rgt] = [rgt] + @nsize
					 WHERE [lft] > @offset;

					# offset parent nodes

					UPDATE `[table]`
					   SET [rgt] = [rgt] + @nsize
					 WHERE [rgt] >= @offset
					   AND [lft] < @offset;

				-- Step 4: move nodes

					SELECT
					@offset := IF(@prgt > @rgt, @prgt - @rgt - 1, @prgt - @rgt - 1 + @nsize);

					UPDATE `[table]`
					   SET [lft] = 0 - ([lft]) + @offset,
						   [rgt] = 0 - ([rgt]) + @offset
					 WHERE [lft] <= 0 - @lft
					   AND [rgt] >= 0 - @rgt;
				',
				[
					'[lft]' => $lft,
					'[rgt]' => $rgt,
				]
			)
			->num(':node_lft', $node[$lft])
			->num(':node_rgt', $node[$rgt])
			->num(':parent_rgt', $parent[$rgt])
			->run();

		static::clear_cache();
	}

	/**
	 * Removes node and its children.
	 */
	static function tree_delete($id)
	{
		$node = static::entry($id);

		if ($node !== null)
		{
			$lft = static::tree_lft();
			$rgt = static::tree_rgt();

			static::statement
				(
					'
					-- initialize parameters

						SELECT
						@lft := :node_lft,
						@rgt := :node_rgt;

						SELECT
						@nsize := @rgt - @lft + 1; # node size

					-- Step 1: remove nodes

						DELETE FROM `[table]`
						 WHERE [lft] >= @lft
						   AND [rgt] <= @rgt;

					-- Step 2: recycle empty space

						# offset adjacent nodes

						UPDATE `[table]`
						   SET [lft] = [lft] - @nsize,
							   [rgt] = [rgt] - @nsize
						 WHERE [lft] > @rgt;

						# offset parent nodes

						UPDATE `[table]`
						   SET [rgt] = [rgt] - @nsize
						 WHERE [lft] < @lft
						   AND [rgt] > @rgt;
					',
					[
						'[lft]' => $lft,
						'[rgt]' => $rgt
					]
				)
				->num(':node_lft', $node[$lft])
				->num(':node_rgt', $node[$rgt])
				->run();

			static::clear_cache();
		}
	}


	// ------------------------------------------------------------------------
	// Collection interface

	/**
	 * To get sub entries in the tree you must specify a depth, by default this
	 * is null which means "retrieve all," you may specify 0 if you only wish to
	 * get the top nodes or you may specify a number between 1 and whatever your
	 * database supports to get entries up to a arbitrary depth cutoff.
	 *
	 * The subtreekey is by default "subentries" but may be customized to be
	 * something else if subentries is already used for something else.
	 *
	 * @return array entries (with subentries if applicable)
	 */
	static
	function tree_entries
		(
			$page = null, $limit = null, $offset = 0, $depth = null,
			array $constraints = null,
			$depthkey = null
		)
	{
		$lft = static::tree_lft();
		$rgt = static::tree_rgt();
		$prt = static::tree_parentkey();

		! empty($depthkey) or $depthkey = 'depth';

		if ($depth != null)
		{
			$constraints[$depthkey] = [ '<=' => $depth ];
		}

		$order = ['entry.lft' => 'asc'];
		$ordersql = \app\SQL::parseorder($order);
		$ORDER_BY = empty($ordersql) ? null : 'ORDER BY '.$ordersql;

		$wheresql = \app\SQL::parseconstraints($constraints);
		$WHERE = empty($wheresql) ? null : 'WHERE '.$wheresql;

		return static::statement
			(
				"
					SELECT entry.*,
					       (
					           SELECT mirror.id
					             FROM `[table]` mirror
					            WHERE mirror.[lft] < entry.[lft]
				                  AND mirror.[rgt] > entry.[rgt]
				                ORDER BY mirror.[rgt] - entry.[rgt] ASC
				                LIMIT 1
							) AS [prt]

					FROM
					(

						SELECT node.*, (COUNT(parent.id) - 1) [depthkey]

						  FROM `[table]` node,
							   `[table]` parent

						 WHERE node.[lft] BETWEEN parent.[lft] AND parent.[rgt]

						 GROUP BY node.id
						 ORDER BY node.[lft]

					) entry

					$WHERE
					$ORDER_BY
					LIMIT :limit OFFSET :offset
				",
				[
					'[lft]' => $lft,
					'[rgt]' => $rgt,
					'[prt]' => $prt,
					'[depthkey]' => $depthkey,
				]
			)
			->page($page, $limit, $offset)
			->run()
			->fetch_all();
	}

	/**
	 * @return array array in array representation of the data
	 */
	static
	function tree_hierarchy
		(
			$page = null, $limit = null, $offset = 0, $depth = null,
			array $constraints = null,
			$subtreekey = null
		)
	{
		! empty($subtreekey) or $subtreekey = 'subentries';

		$depthkey = '__mjolnir_depth';

		$entries = static::tree_entries
			($page, $limit, $offset, $depth, $constraints, $depthkey);

		$hierarchy = [];
		$parents = [];
		$depth_level = 0;

		foreach ($entries as &$entry)
		{
			// gurantee subtree
			$entry[$subtreekey] = [];
			// include into hierarchy
			if ($entry[$depthkey] == 0)
			{
				$depth_level = $entry[$depthkey];
				$parents[$depth_level] = &$entry;
				$hierarchy[] = &$entry;
			}
			else if ($entry[$depthkey] == $depth_level)
			{
				$depth_level = $entry[$depthkey];
				$parents[$depth_level] = &$entry;
				$parents[$depth_level - 1][$subtreekey][] = &$entry;
			}
			else if ($entry[$depthkey] > $depth_level)
			{
				// its impossible to have depth jump more then 1 unit, so we
				// simply perform a 1 unit increment each time
				$depth_level++;
				$parents[$depth_level] = &$entry;
				$parents[$depth_level - 1][$subtreekey][] = &$entry;
			}
			else # depth != 0 && depth < depth_level
			{
				// its possible for depth to jump several units, in cases such
				// as a very long branch ending
				$depth_level -= $depth_level - $entry[$depthkey];
				$parents[$depth_level] = &$entry;
				$parents[$depth_level - 1][$subtreekey][] = &$entry;
			}
		}

		return $hierarchy;
	}

	// ------------------------------------------------------------------------
	// Utilities

	/**
	 * The virtual root is defined as the node that would hold the elements in
	 * the tree; since we support multiple trees instead of using a hidden
	 * master node, the virtual root is defined as min(lft), max(rgt)
	 *
	 * @return array (lft, rgt)
	 */
	static function tree_virtual_root()
	{
		$lft = static::tree_lft();
		$rgt = static::tree_rgt();

		$vroot = static::statement
			(
				'
					SELECT (min([lft]) - 1) [lft],
					       (max([rgt]) + 1) [rgt]
					  FROM `[table]`
				',
				[
					'[lft]' => $lft,
					'[rgt]' => $rgt,
				]
			)
			->run()
			->fetch_entry();

		return $vroot[$lft] !== null || $vroot[$rgt] !== null ? $vroot : null;
	}

	/**
	 * @return boolean true if node is child of parent or equal to the parent
	 */
	static function tree_node_is_child_of_parent($node_id, $parent_id)
	{
		$lft = static::tree_lft();
		$rgt = static::tree_rgt();

		$node = static::entry($node_id);
		$parent = static::entry($parent_id);

		return $node[$lft] >= $parent[$lft]
		    && $node[$rgt] <= $parent[$rgt];
	}

	/**
	 * @return null|int parent for entry
	 */
	static function tree_parent($id, $constraints = null)
	{
		$constraints != null or $constraints = [];
		
		$WHERE = \app\SQL::parseconstraints($constraints, true);
		
		if (empty($WHERE))
		{
			$WHERE = 'WHERE ';
		}
		else # ! empty($WHERE)
		{
			$WHERE = $WHERE.' AND ';
		}
		
		$WHERE .= 'target.lft > entry.lft AND target.lft < entry.rgt';

		$result = static::statement
			(
				__METHOD__,
				"
					SELECT entry.id
					  FROM `".static::table()."` entry

					  JOIN `".static::table()."` target
						ON target.id = :entry_id

					$WHERE
					 ORDER BY entry.lft DESC
					 LIMIT 1
				"
			)
			->num(':entry_id', $id)
			->run()
			->fetch_all();

		if ( ! empty($result))
		{
			return $result[0]['id'];
		}
		else # no parent
		{
			return null;
		}
	}

} # trait
