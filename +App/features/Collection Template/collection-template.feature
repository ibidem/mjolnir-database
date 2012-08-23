@ibidem @caching @ancillary
Feature: Simple collection template
  In order for collection functions to work.
  As a developer
  I need to be able to retrieve entries, an entry, search and test for existence of pairs.

  Background:
	Given a mock database with ids "1, 2, 3, 4, 5, 6, 7, 8" and titles "a, d, a, b, b, c, a, b"

  Scenario Outline: Testing if an item exists.
	When I ask for the existence of "<title>"
	Then I should get back the boolean "<answer>"

  Scenarios: 
	| title           | answer            |
	| a               | true              |
	| b               | true              |
	| x               | false             |
	| 0               | false             |

  Scenario Outline: Testing if an item exists, with a custom column.
	When I ask for the existence of "<id>", under the key "id"
	Then I should get back the boolean "<answer>"

  Scenarios: 
	| id              | answer            |
	| 1               | true              |
	| 2               | true              |
	| 9               | false             |
	| 0               | false             |

  Scenario Outline: Retrieving an item.
	When I ask for the entry "<id>"
	Then I should get the value "<value>"
	
  Scenarios:
	| id              | value             |
	| 1               | 1                 |
	| 2               | 2                 |
	| 9               | null              |
	| 0               | null              |

  Scenario: Retrieving an item based on constraint
	When I ask for the entries
	Then I should get the entries "1, 2, 3, 4, 5, 6, 7, 8"
	When I ask for the entry with the title "d"
	Then I should get the value "2"

  Scenario: Retrieving a set of items.
	When I ask for the entries
	Then I should get the entries "1, 2, 3, 4, 5, 6, 7, 8"

  Scenario Outline: Paged set of items
	When I limit entries to "<page>", "<limit>", "<offset>"
	Then I should get the entries "<entries>"

  Scenarios:
	| page   | limit  | offset | entries          |
	| 1      | 3      | 0      | 1, 2, 3          |
	| 2      | 5      | 0      | 6, 7, 8          |
	| 2      | 2      | 1      | 4, 5             |
	| 1      | 6      | 2      | 3, 4, 5, 6, 7, 8 |
	| 1      | 3      | 2      | 3, 4, 5          |

  Scenario Outline: Limiting results though constraints
	When I constraint entries to "<constraints>"
	Then I should get the entries "<entries>"

  Scenarios:
	| constraints         | entries |
	| title => a          | 1, 3, 7 |
	| title => x          |         |
	| id => 2, title => d | 2       |
	
  Scenario Outline: Sorting results
	When I sort the entries to "<sorting>"
	Then I should get the entries "<entries>"

  Scenarios:
	| sorting                  | entries                |
	| title => desc            | 2, 6, 4, 5, 8, 1, 3, 7 |
	| id => desc               | 8, 7, 6, 5, 4, 3, 2, 1 |
	| title => asc, id => desc | 7, 3, 1, 8, 5, 4, 6, 2 |

  Scenario: counting all entries
	When I ask for the count
	Then I should get the value "8"
	
  Scenario: counting all entries after a delete
	When I ask for the count
	 And I delete the entry "2"
	 And I ask for the count again
	Then I should get the value "7"

  Scenario: deleteing an entry
	When I ask for the count
	 And I delete the entry "2"
	 And I ask for the entry with the title "d"
	Then I should get the value "null"