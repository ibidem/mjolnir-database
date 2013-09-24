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
	protected static
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
			throw new \Exception('Integrity violation: missing parent key');
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
			$input[$lft] = $vroot['rgt'] + 1;
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
				__TRAIT__.'::'.__FUNCTION__,
				"
					#! rgt -> $rgt
					UPDATE :table
					   SET $rgt = $rgt + :rgtoffset
					 WHERE $rgt = :offsetidx
				"
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
		$lft = static::tree_lft();
		$rgt = static::tree_rgt();

		static::statement
			(
				__TRAIT__.'::'.__FUNCTION__,
				"
					#! rgt -> $rgt, lft -> $lft
					UPDATE :table
					   SET $lft = $lft + :lftoffset,
						   $rgt = $rgt + :rgtoffset
					 WHERE $lft > :offsetidx
				"
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
			array $input,
			array $strs, array $bools = null, array $nums = null
		)
	{
		$lft = static::tree_lft();
		$rgt = static::tree_rgt();
		$prt = static::tree_parentkey();

		if (isset($input[$lft]) or isset($input[$rgt]))
		{
			throw new \Exception('Integrity violation: attempted to hardcode lft and rgt values though tree_inserter');
		}

		if ( ! isset($input[$prt]))
		{
			throw new \Exception('Integrity violation: missing parent key');
		}

		// @todo CLEANUP implementation
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
				null, # unkeyed
				"
					SELECT entry.*

					FROM
					(

						SELECT node.*, (COUNT(parent.id) - 1) $depthkey

						  FROM :table node,
							   :table parent

						 WHERE node.$lft BETWEEN parent.$lft AND parent.$rgt

						 GROUP BY node.id
						 ORDER BY node.$lft

					) entry

					$WHERE
					$ORDER_BY
					LIMIT :limit OFFSET :offset
				"
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
				$depth_level++;
				$parents[$depth_level] = &$entry;
				$parents[$depth_level - 1][$subtreekey][] = &$entry;
			}
			else # depth != 0 && depth < depth_level
			{
				$depth_level--;
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

		return static::statement
			(
				null, # unkeyed
				"
					SELECT min($lft) $lft,
					       max($rgt) $rgt
					  FROM :table
				"
			)
			->run()
			->fetch_entry();
	}

} # trait
