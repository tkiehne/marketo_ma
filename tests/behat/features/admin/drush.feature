@api @marketo_ma_drush
Feature: Marketo MA Drush features
  In order to prove that drush funcitons are working properly
  As a developer
  I need all of these tests to run successfully

  Background: Fresh module install
    Given all Marketo MA modules are clean and using "marketo_test_settings"
    
  Scenario Outline: Ensure all expected drush commands are available and functioning
    When I run drush "help" "<command>"
    Then drush output should contain "<description>"
    
    When I run drush "help" "<alias>"
    Then drush output should contain "<description>"
    
  Examples:
    | command      | alias | description        |
    | mma-fields   | mmaf  | Get Marketo fields |
    | mma-get-lead | mmal  | Get Marketo lead   |
    | mma-verify   | mmav  | Verify this site   |

  @live
  Scenario: Execute drush commands
    Given I populate the Marketo MA config using "marketo_settings"
    
    When I run drush "mma-verify"
    Then drush output should contain "Successfully connected to Marketo"
    
    When I run drush "mma-fields"
    Then drush output should contain " Name "
    And drush output should contain " Label "