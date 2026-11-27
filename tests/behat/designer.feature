@local @local_vravatar
Feature: Users design their own VR avatar
  In order to look like myself in the live rooms of VR room activities
  As a user
  I need to choose the parts of my avatar on the My VR avatar page and save them

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | One      | student1@example.com |

  @javascript
  Scenario: Open My VR avatar from the profile, choose parts, save and see them after a reload
    Given I log in as "student1"
    And I follow "Profile" in the user menu
    When I click on "My VR avatar" "link"
    Then I should see "How you look in the live rooms of VR room activities"
    And I click on "Who sees my avatar?" "text"
    And I should see "The same avatar is used in every live room on this site"
    And I should see "Only the people in the same live room as you see your avatar"
    And I should see "You have not saved an avatar yet"
    And I should see "Pick a look to start, then change anything"
    And I click on "Face" "button" in the "[role='tablist']" "css_element"
    And I set the field "Rounded square" to "1"
    And I click on "Body" "button" in the "[role='tablist']" "css_element"
    And the field "Capsule" matches value "1"
    And I set the field "Slim" to "1"
    And I set the field "Teal" in the "Body colour" "fieldset" to "1"
    And I set the field "Yellow" in the "Accent colour" "fieldset" to "1"
    And I click on "Hair" "button" in the "[role='tablist']" "css_element"
    And I set the field "Bun" to "1"
    And I set the field "Auburn" in the "Hair colour" "fieldset" to "1"
    And I click on "Extras" "button" in the "[role='tablist']" "css_element"
    And I set the field "Beanie" to "1"
    And I click on "Glasses and frame colour" "button" in the "#local-vravatar-section-extras" "css_element"
    And I set the field "Classic frames" to "1"
    And I set the field "Show my profile picture on my avatar" to "1"
    And I press "Save"
    And I should see "Your avatar was saved." in the "[data-region='status']" "css_element"
    And I should see "Stop using my avatar (standard look)"
    And I reload the page
    And I should not see "You have not saved an avatar yet"
    And the field "Rounded square" matches value "1"
    And I click on "Body" "button" in the "[role='tablist']" "css_element"
    And the field "Slim" matches value "1"
    And the field "Capsule" matches value ""
    And the field "Teal" in the "Body colour" "fieldset" matches value "1"
    And the field "Blue" in the "Body colour" "fieldset" matches value ""
    And the field "Yellow" in the "Accent colour" "fieldset" matches value "1"
    And I click on "Hair" "button" in the "[role='tablist']" "css_element"
    And the field "Bun" matches value "1"
    And the field "Auburn" in the "Hair colour" "fieldset" matches value "1"
    And I click on "Extras" "button" in the "[role='tablist']" "css_element"
    And the field "Beanie" matches value "1"
    And the field "Classic frames" matches value "1"
    And the field "Show my profile picture on my avatar" matches value "1"

  @javascript
  Scenario: Reset to default puts the default choices back in the form until saved
    Given I log in as "student1"
    And I visit "/local/vravatar/index.php"
    And I click on "Extras" "button" in the "[role='tablist']" "css_element"
    And I click on "Glasses and frame colour" "button" in the "#local-vravatar-section-extras" "css_element"
    And I set the field "Classic frames" to "1"
    And I click on "Body" "button" in the "[role='tablist']" "css_element"
    And I set the field "Broad" in the "Body shape" "fieldset" to "1"
    And I set the field "Red" in the "Body colour" "fieldset" to "1"
    And I press "Save"
    And I should see "Your avatar was saved." in the "[data-region='status']" "css_element"
    When I press "Reset to default"
    Then I should see "The default choices are back." in the "[data-region='status']" "css_element"
    And I should see "Unsaved changes" in the "[data-region='savebar']" "css_element"
    And the field "Capsule" matches value "1"
    And the field "Broad" in the "Body shape" "fieldset" matches value ""
    And I click on "Extras" "button" in the "[role='tablist']" "css_element"
    And the field "Classic frames" matches value ""
    And the field "No glasses" matches value "1"
    And I reload the page
    And I click on "Body" "button" in the "[role='tablist']" "css_element"
    And the field "Broad" in the "Body shape" "fieldset" matches value "1"
    And the field "Red" in the "Body colour" "fieldset" matches value "1"

  Scenario: Without JavaScript the choices are saved and the avatar can be removed again
    Given I log in as "student1"
    And I visit "/local/vravatar/index.php"
    Then I should not see "Example context"
    When I set the field "Long straight" to "1"
    And I set the field "Sun hat" to "1"
    And I press "Save"
    Then I should see "Your avatar was saved."
    And the field "Long straight" matches value "1"
    And the field "Sun hat" matches value "1"
    And I press "Stop using my avatar (standard look)"
    And I should see "Your avatar was removed. You have the standard look again."
    And I should see "You have not saved an avatar yet"
    # The standard look again: short hair and no hat.
    And the field "Short" in the "Hairstyle" "fieldset" matches value "1"
    And the field "No hat" matches value "1"

  Scenario: The site can turn off profile pictures on avatars
    Given the following config values are set as admin:
      | config       | value | plugin       |
      | allowpicture | 0     | local_vravatar |
    And I log in as "student1"
    When I visit "/local/vravatar/index.php"
    Then I should see "The site does not show profile pictures on avatars."
    And I should not see "Example context"
    And I should not see "Show my profile picture on my avatar"

  Scenario: An administrator finds the profile picture setting on by default
    Given I log in as "admin"
    When I navigate to "Plugins > Local plugins > VR avatar" in site administration
    Then the field "Profile picture on avatars" matches value "1"

  @javascript @local_vravatar_webgl
  Scenario: The 3D preview is shown where the browser has WebGL
    Given I log in as "student1"
    When I visit "/local/vravatar/index.php"
    Then "[data-region='canvas']" "css_element" should be visible
    And I should not see "This browser cannot show the 3D preview"
    And I should see "Drag the preview, or use the left and right arrow keys, to turn it."
    And I should not see "Example context"
    And I click on "Face" "button" in the "[role='tablist']" "css_element"
    And the "aria-pressed" attribute of "Show the whole avatar" "button" should contain "false"
    When I press "Show the whole avatar"
    Then the "aria-pressed" attribute of "Show the whole avatar" "button" should contain "true"
    And I press "Show the whole avatar"
    And the "aria-pressed" attribute of "Show the whole avatar" "button" should contain "false"
    When I click on "Costume" "button" in the "[role='tablist']" "css_element"
    Then the "aria-pressed" attribute of "Show the whole avatar" "button" should contain "true"
    # The costume tab opens on its first category (the costumes made in code).
    And I press "Fun and fantasy"
    And I set the field "Pirate captain" to "1"
    And "[data-region='canvas']" "css_element" should be visible
    And "[data-render='face'] img" "css_element" should exist

  @javascript
  Scenario: Choose a new hairstyle, a skin tone and a face, save and see them after a reload
    Given I log in as "student1"
    And I visit "/local/vravatar/index.php"
    Then I should see "Looks" in the "[role='tablist']" "css_element"
    And I should see "Face" in the "[role='tablist']" "css_element"
    And I should see "Hair" in the "[role='tablist']" "css_element"
    And I should see "Body" in the "[role='tablist']" "css_element"
    And I should see "Hands" in the "[role='tablist']" "css_element"
    And I should see "Extras" in the "[role='tablist']" "css_element"
    And I click on "Face" "button" in the "[role='tablist']" "css_element"
    # The standard look has a real skin tone and round eyes drawn on the skin.
    And the field "Same as body colour" matches value ""
    And the field "Round" in the "Eyes" "fieldset" matches value "1"
    And the field "No facial hair" matches value "1"
    And the field "Show ears" matches value ""
    When I set the field "Medium" to "1"
    And I set the field "Oval" to "1"
    And I click on "Eyes" "button" in the "#local-vravatar-section-head" "css_element"
    And I set the field "Almond" to "1"
    And I click on "Brows" "button" in the "#local-vravatar-section-head" "css_element"
    And I set the field "Arched" to "1"
    And I click on "Nose and mouth" "button" in the "#local-vravatar-section-head" "css_element"
    And I set the field "Pointed" in the "Nose" "fieldset" to "1"
    And I click on "Ears" "button" in the "#local-vravatar-section-head" "css_element"
    And I set the field "Show ears" to "1"
    And I click on "Hair" "button" in the "[role='tablist']" "css_element"
    And I set the field "Afro" to "1"
    And I set the field "Purple" in the "Hair colour" "fieldset" to "1"
    And I click on "Facial hair" "button" in the "#local-vravatar-section-hair" "css_element"
    And I set the field "Goatee" to "1"
    And I press "Save"
    And I should see "Your avatar was saved." in the "[data-region='status']" "css_element"
    And I reload the page
    Then the field "Medium" matches value "1"
    And the field "Same as body colour" matches value ""
    And the field "Oval" matches value "1"
    And the field "Almond" matches value "1"
    And the field "Arched" matches value "1"
    And the field "Pointed" in the "Nose" "fieldset" matches value "1"
    And the field "Show ears" matches value "1"
    And the field "Goatee" matches value "1"
    And the field "No facial hair" matches value ""
    And I click on "Hair" "button" in the "[role='tablist']" "css_element"
    And the field "Afro" matches value "1"
    And the field "No hair" matches value ""
    And the field "Purple" in the "Hair colour" "fieldset" matches value "1"
    And I click on "Body" "button" in the "[role='tablist']" "css_element"
    And the field "Purple" in the "Body colour" "fieldset" matches value ""

  @javascript
  Scenario: Choose hands, start from a look, undo it and save
    Given I log in as "student1"
    And I visit "/local/vravatar/index.php"
    When I click on "Hands" "button" in the "[role='tablist']" "css_element"
    Then the field "Cartoon" in the "Hand style" "fieldset" matches value "1"
    And the field "Same as skin" matches value "1"
    And I set the field "Robot" in the "Hand style" "fieldset" to "1"
    And I set the field "Teal" in the "Hand colour" "fieldset" to "1"
    And I should see "Unsaved changes" in the "[data-region='savebar']" "css_element"
    And I click on "Looks" "button" in the "[role='tablist']" "css_element"
    When I press "Explorer"
    Then I should see "The look \"Explorer\" is in the preview" in the "[data-region='status']" "css_element"
    And I click on "Hands" "button" in the "[role='tablist']" "css_element"
    And the field "Cartoon" in the "Hand style" "fieldset" matches value "1"
    When I press "Undo"
    Then I should see "Undone." in the "[data-region='status']" "css_element"
    And the field "Robot" in the "Hand style" "fieldset" matches value "1"
    And I press "Save"
    And I should see "Your avatar was saved." in the "[data-region='status']" "css_element"
    And I should not see "Unsaved changes" in the "[data-region='savebar']" "css_element"
    And I reload the page
    And I click on "Hands" "button" in the "[role='tablist']" "css_element"
    And the field "Robot" in the "Hand style" "fieldset" matches value "1"
    And the field "Teal" in the "Hand colour" "fieldset" matches value "1"

  @javascript
  Scenario: Choose a costume and the tricorne, save and see them kept after a reload
    Given I log in as "student1"
    And I visit "/local/vravatar/index.php"
    When I click on "Costume" "button" in the "[role='tablist']" "css_element"
    Then I should see "Fun and fantasy"
    And I should see "Computers"
    And I should see "Travel"
    And I press "Suits"
    And I should see "by Ian MacGillivray, CC BY 3.0"
    And the field "No costume" matches value "1"
    When I press "Fun and fantasy"
    And I set the field "Pirate captain" to "1"
    And I click on "Extras" "button" in the "[role='tablist']" "css_element"
    And I set the field "Tricorne" to "1"
    And I press "Save"
    And I should see "Your avatar was saved." in the "[data-region='status']" "css_element"
    And I reload the page
    And the field "Tricorne" matches value "1"
    And I click on "Costume" "button" in the "[role='tablist']" "css_element"
    Then the field "Pirate captain" matches value "1"
    And the field "No costume" matches value ""
    When I press "Computers"
    And I set the field "Laptop" to "1"
    And I press "Save"
    And I should see "Your avatar was saved." in the "[data-region='status']" "css_element"
    And I reload the page
    And I click on "Costume" "button" in the "[role='tablist']" "css_element"
    Then the field "Laptop" matches value "1"
    And the field "Pirate captain" matches value ""

  Scenario: Without JavaScript a costume is saved and kept
    Given I log in as "student1"
    And I visit "/local/vravatar/index.php"
    When I set the field "Mini woman C" to "1"
    And I press "Save"
    Then I should see "Your avatar was saved."
    And the field "Mini woman C" matches value "1"
    And I should see "Credits for the costume models"
    And I should see "Mini Characters (character-male-a)"
    And I should not see "Blocky"
    And I should not see "Pirate hat"
