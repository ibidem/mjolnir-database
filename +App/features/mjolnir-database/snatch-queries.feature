@mjolnir @caching @ancillary
Feature: Simple easy-to-use selective queries
  In order for snatch queries to work.
  As a developer
  I need to be able to store and retrieve values consistently.

  Background:
	Given a mock database with ids "1, 2, 3, 4, 5, 6, 7, 8" and titles "a, d, a, b, b, c, a, b"

  Scenario: Basic retrieval.
	Given a querie "*"
	 When I execute the querie
	 Then I should get the ids "1, 2, 3, 4, 5, 6, 7, 8"
      And I should get the titles "a, d, a, b, b, c, a, b"

  Scenario: Anything can be retrieved any number of times.
	Given a querie "*"
	 When I execute the querie
	 Then I should get the ids "1, 2, 3, 4, 5, 6, 7, 8"
     When I execute the querie again
     Then I should get the ids "1, 2, 3, 4, 5, 6, 7, 8"

  Scenario: Retrieving from a database after an update.
	Given a querie "*"
	 When I execute the querie
	 Then I should get the ids "1, 2, 3, 4, 5, 6, 7, 8"
	 When I add an item with id "9" and title "a" to the database
      And I execute the querie again
	 Then I should get the ids "1, 2, 3, 4, 5, 6, 7, 8, 9"

  Scenario Outline: Retrieving a limited amount of results.
	Given a querie "*"
 	 When I limit the querie to page "<page>", limit "<limit>" and offset "<offset>"
	  And I execute the querie
	 Then I should get the ids "<result>"

  Scenarios:
	| page | limit | offset | result  |
	| 1    | 3     | 0      | 1, 2, 3 |
	| 1    | 3     | 1      | 2, 3, 4 |
	| 2    | 3     | 2      | 6, 7, 8 |
	| 2    | 3     | 0      | 4, 5, 6 |

  Scenario Outline: Retrieving ordered results.
	Given a querie "*"
	 When I limit the querie to page "1", limit "5" and offset "0"
	  And I sort the query by "<sorting>"
	  And I execute the querie
	 Then I should get the ids "<result>"

  Scenarios:
	| sorting                  | result        |
	| id => asc                | 1, 2, 3, 4, 5 |
	| id => Desc               | 8, 7, 6, 5, 4 |
	| id => aSc, title => DESC | 1, 2, 3, 4, 5 |
	| title => asC, id => ASC  | 1, 3, 7, 4, 5 |

  Scenario: Retrieving ordered results after an update.
	Given a querie "*"
	 When I limit the querie to page "1", limit "5" and offset "0"
	  And I sort the query by "id => desc"
	  And I execute the querie
	 Then I should get the ids "8, 7, 6, 5, 4"
	 When I add an item with id "9" and title "a" to the database
	  And I execute the querie
	 Then I should get the ids "9, 8, 7, 6, 5"

  Scenario Outline: Retrieving constraint values.
	Given a querie "*"
	 When I limit the querie to page "1", limit "5" and offset "0"
      And I sort the query by "id => ASC"
	  And I constraint the query to "<constraints>"
	  And I execute the querie
	 Then I should get the ids "<result>"

  Scenarios:
	| constraints            | result  |
	| title => a             | 1, 3, 7 |
	| title => b             | 4, 5, 8 |
	| CONCAT(title,id) => b4 | 4       |
	| id > 5 => true         | 6, 7, 8 |
	| id => 3, title => a    | 3       |
	| id => 3, title => b    |         |

  Scenario: Retrieving constraint results after an update.
	Given a querie "*"
	 When I limit the querie to page "1", limit "5" and offset "0"
	  And I sort the query by "id => ASC"
	  And I constraint the query to "title => a"
	  And I execute the querie
	 Then I should get the ids "1, 3, 7"
	 When I add an item with id "9" and title "a" to the database
	  And I execute the querie
	 Then I should get the ids "1, 3, 7, 9"