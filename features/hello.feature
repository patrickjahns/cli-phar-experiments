Feature: hello world

  Scenario: I can run the command without providing further input
    When I run the command "hello"
    Then the command output should contain:
    """
    Hello World
    """

