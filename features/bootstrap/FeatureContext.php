<?php

use Drupal\DrupalExtension\Context\RawDrupalContext;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends RawDrupalContext {
  private $pagedNumbers = null;

  /**
   * Initializes context.
   *
   * Every scenario gets its own context instance.
   * You can also pass arbitrary arguments to the
   * context constructor through behat.yml.
   */
  public function __construct() {}

  /**
   * Behat test for logging in.
   *
   * @Given I am logged in as user :name
   *
   * @param string $name
   */
  public function iAmLoggedInAsUser($name) {
    $domain = $this->getMinkParameter('base_url');

    // Pass base url to drush command.
    $uli = $this->getDriver('drush')->drush('uli', [
      "--name '" . $name . "'",
      '--browser=0',
      "--uri=$domain",
    ]);

    // Trim EOL characters.
    $uli = trim($uli);

    // Log in.
    $this->getSession()->visit($uli);
  }

  /**
   * Scroll the window vertically by N pixels.
   *
   * @Then /^I scroll vertically by (-?\d+) pixels$/
   *
   * @param int $number
   *   Allowed selectors: positive and negative numbers
   *
   * @throws Exception
   */
  public function scrollByN(int $number) {
    $function = <<<JS
      (function(){
        window.scrollBy(0, $number);
      })()
    JS;

    $this->getSession()->evaluateScript($function);
  }

  /**
   * Hover over an element.
   *
   * See https://stackoverflow.com/questions/18499851/how-do-i-tell-behat-mink-to-hover-over-an-element-on-a-webpage
   *
   * @When /^I hover over the element "((\\\")?.*)"$/
   *
   * @param string $locator
   */
  public function iHoverOverTheElement($locator) {
    $session = $this->getSession();
    $element = $session->getPage()
      ->find('css', $locator);

    if (NULL === $element) {
      throw new InvalidArgumentException(sprintf('Could not evaluate CSS selector: "%s"', $locator));
    }

    $element->mouseOver();
  }

  /**
   * Click a specific CSS selector's element with JS.
   *
   * @When /^I click the element "((\\\")?.*)" with JS$/
   *
   * @param string $selector
   *
   * @throws Exception
   */
  public function iClickTheElementJS($selector) {
    $function = <<<JS
      (function(){
        let elem = document.querySelector("$selector");
        if (!elem) {
          return false;
        }
        elem.click();
        return elem !== false && elem !== undefined;
      })()
    JS;

    if ($this->getSession()->evaluateScript($function)) {
      return;
    }

    throw new Exception(__METHOD__ . ' failed');
  }

  /**
   * Remove CSS class from selector.
   *
   * @Then I remove the :class CSS class from :selector
   *
   * @param string $class
   * @param string $selector
   *
   * @throws Exception
   */
  public function removeClassFromSelector(string $class, string $selector) {
    $function = <<<JS
      (function(){
        var elem = document.querySelector("$selector");
        elem.classList.remove("$class");
        return elem !== null && elem !== undefined;
      })()
    JS;

    if ($this->getSession()->evaluateScript($function)) {
      return;
    }

    throw new Exception(__METHOD__ . ' failed');
  }

  /**
   * Check if a single element exists on the page.
   *
   * @Then I should see that the :selector element exists
   *
   * @param string $selector
   *
   * @throws Exception
   */
  public function singleElementExists(string $selector): void {
    $function = <<<JS
      (function(){
        var elem = document.querySelector("$selector");
        return elem !== null && elem !== undefined;
      })()
    JS;

    if ($this->getSession()->evaluateScript($function)) {
      return;
    }

    throw new Exception(__METHOD__ . ' failed');
  }

  /**
   * Check if a single element does not exist on the page.
   *
   * @Then I should see that the :selector element does not exist
   *
   * @throws Exception
   */
  public function singleElementDoesNotExist($selector) {
    $function = <<<JS
      (function(){
        var elem = document.querySelector("$selector");
        return elem !== null && elem !== undefined;
      })()
    JS;

    if (!$this->getSession()->evaluateScript($function)) {
      return;
    }

    throw new Exception(__METHOD__ . ' failed');
  }

  /**
   * Submit a form.
   *
   * @Then I submit the :selector form
   *
   * @param string $selector
   *
   * @throws Exception
   */
  public function submitForm($selector) {
    $function = <<<JS
      (function(){
        var elem = document.querySelector("$selector");
        elem.submit();
        return elem !== null && elem !== undefined;
      })()
    JS;

    if ($this->getSession()->evaluateScript($function)) {
      return;
    }

    throw new Exception(__METHOD__ . ' failed');
  }

  /**
   * Check if multiple elements exist on the page.
   *
   * @Then I should see that multiple :selector elements exist
   *
   * @throws Exception
   */
  public function multipleElementsExist($selector) {
    $function = <<<JS
      (function(){
        var elem = document.querySelectorAll("$selector");
        return elem.length > 1;
      })()
    JS;

    if ($this->getSession()->evaluateScript($function)) {
      return;
    }

    throw new Exception(__METHOD__ . ' failed');
  }

  /**
   * Check if active menu has gold underline.
   *
   * @Then I should see the gold underline exists on :selector menu item
   *
   * @throws Exception
   */
  public function goldUnderlineExists($selector) {
    $function = <<<JS
      (function(){
        var elem = window.getComputedStyle(document.querySelector("$selector"), "::after").getPropertyValue('background');
        if (elem === "rgba(0, 0, 0, 0) linear-gradient(to right, rgba(0, 0, 0, 0) 0.5%, rgb(255, 198, 39) 0.5%) repeat scroll 0% 0% / auto padding-box border-box" ||
        elem === "rgb(255, 198, 39) none repeat scroll 0% 0% / auto padding-box border-box") {
          return elem;
        }
        else {
          return false;
        }
      })()
    JS;

    if ($this->getSession()->evaluateScript($function)) {
      return;
    }

    throw new Exception(__METHOD__ . ' failed');
  }

  /**
   * Check if pager has chevron.
   *
   * @Then I should see the chevron icon exists on the :selector pager item
   *
   * @throws Exception
   */
  public function chevronExists($selector) {
    $function = <<<JS
      (function(){
        var elem = window.getComputedStyle(document.querySelector("$selector"), "::after").getPropertyValue('content');
        if (elem === 'none') {
          var elem = window.getComputedStyle(document.querySelector("$selector"), "::before").getPropertyValue('content');
        }

        if (elem && elem !== 'none') {
          switch (true) {
            case elem === `url("data:image/svg+xml; utf8, <svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 448 512' data-fa-i2svg=''><path fill='currentColor' d='M207.029 381.476L12.686 187.132c-9.373-9.373-9.373-24.569 0-33.941l22.667-22.667c9.357-9.357 24.522-9.375 33.901-.04L224 284.505l154.745-154.021c9.379-9.335 24.544-9.317 33.901.04l22.667 22.667c9.373 9.373 9.373 24.569 0 33.941L240.971 381.476c-9.373 9.372-24.569 9.372-33.942 0z'></path></svg>")`:
              return true;
            default:
              return false;
          }
        }
        return false;
      })()
    JS;

    if ($this->getSession()->evaluateScript($function)) {
      return;
    }

    throw new Exception(__METHOD__ . ' failed');
  }

  /**
   * Drag selector item vertically by X pixels.
   *
   * @Then I drag :selector vertically by :number pixels
   *
   * @param $selector
   * @param $number
   *
   * @return void
   * @throws Exception
   */
  public function dragVerticallyByXpx($selector, $number) {
    $function = <<<JS
      (function(){
        // We create 3 mouse events using MouseEvent interface,
        // one for mousedown to initiate the process,
        // one for mousemove to handle to movement
        // and one for mouseup to terminate the process
        let element = document.querySelector("$selector");

        const mouseDownEvent = new MouseEvent('mousedown', {
          clientX: element.getBoundingClientRect().left,
          clientY: element.getBoundingClientRect().top,
          bubbles: true,
          cancelable: true
        });

        const mouseMoveEvent = new MouseEvent('mousemove', {
          clientX: element.getBoundingClientRect().left,
          clientY: element.getBoundingClientRect().top $number,
          bubbles: true,
          cancelable: true
        });

        const mouseUpEvent = new MouseEvent('mouseup', {
          bubbles: true,
          cancelable: true
        });

        // Dispatch the mousedown event to the element that has the listener
        // For mousemove, the listener may be the parent or even the document
        // Dispatch mouseup to terminate the process
        if (element.dispatchEvent(mouseDownEvent) === false && element.dispatchEvent(mouseMoveEvent) === false && element.dispatchEvent(mouseUpEvent) === true) {
          return true;
        } else {
          return false;
        }
      })()
    JS;

    if ($this->getSession()->evaluateScript($function)) {
      return;
    }

    throw new Exception(__METHOD__ . ' failed');
  }

  /**
   * Check the label for a checkbox.
   *
   * @Then I click the checkbox label for :checkbox
   */
  public function iClickTheCheckboxLabelFor($checkbox) {
    $function = <<<JS
      (function(){
        document.querySelectorAll('[for=${checkbox}]')[0].click();
      })()
    JS;

    try {
      return $this->getSession()->executeScript($function);
    }
    catch (Exception $e) {
      throw new Exception(__METHOD__ . ' failed');
    }
  }

  /**
   * @Given I perform actions and checks for menu item :menuItem
   */
  public function iPerformActionsAndChecksForMenuItem($menuItem) {
    $session = $this->getSession();
    print("Start item\n");

    try {
      $this->iClickTheElement('.accept-btn');
    }
    catch (Exception $e) {
    }

    $session->getPage()->clickLink('Add block');
    print("Click Add block \n");

    $start = microtime(TRUE);
    $end = $start + 5;
    $continue = TRUE;
    while ($continue && (microtime(TRUE) < $end)) {
      if ($this->getSession()->getPage()->hasLink('Create content block')) {
        $this->getSession()->getPage()->clickLink('Create content block');
        $continue = FALSE;
        print("Click Create content block\n");
      }
      usleep(500000);
    }

    $start = microtime(TRUE);
    $end = $start + 5;
    $continue = TRUE;
    if (str_contains($menuItem, 'Web Directory')) {
      # The Web Directory link was giving problems, so I'm accessing it this way
      $this->iClickTheElement('#drupal-off-canvas > div > ul > li:nth-child(29) > a');
    }
    else {
      while ($continue && (microtime(TRUE) < $end)) {
        try {
          if ($this->getSession()
            ->getPage()
            ->hasLink(str_replace('\\"', '"', $menuItem))) {
            $session->getPage()->clickLink(str_replace('\\"', '"', $menuItem));
            $continue = FALSE;
            print('Click ' . $menuItem . "\n");
          }
        }
        catch (Exception $e) {
        }
        usleep(500000);
      }
    }

    $start = microtime(TRUE);
    $end = $start + 5;
    $continue = TRUE;

    $functionCheckAppearanceDetail = <<<JS
      (function(){
        var elem = document.querySelector("summary[aria-controls*=group-appearance-settings]");
        return elem !== null && elem !== undefined;
      })()
    JS;

    while ($continue && (microtime(TRUE) < $end)) {
      if ($this->getSession()->evaluateScript($functionCheckAppearanceDetail)) {
        $this->iClickTheElement('summary[aria-controls*=group-appearance-settings]');
        $this->scrollIntoView('summary[aria-controls*=group-appearance-settings]');
        $continue = FALSE;
        print("Click Appearence settings details\n");
      }
      usleep(500000);
    }

    // $this->singleElementExists('table[id*=field-anchor-menu-settings-values]');
    $this->xNumberElementsExist(2, 'select[data-drupal-selector*=edit-settings-block-form-field-spacing]');
    $this->wait(2);
    print('End item');
  }

  /**
   * Click a specific CSS selector's element.
   *
   * @When /^I click the element "((\\\")?.*)"$/
   *
   * @param string $locator
   */
  public function iClickTheElement($locator) {
    $session = $this->getSession();
    $element = $session->getPage()
      ->find('css', $locator);

    if (NULL === $element) {
      throw new InvalidArgumentException(sprintf('Could not evaluate CSS selector: "%s"', $locator));
    }

    $element->click();
  }

  /**
   * Scroll selector into view.
   *
   * See https://gist.github.com/MKorostoff/c94824a467ffa53f4fa9?permalink_comment_id=2095785#gistcomment-2095785
   *
   *  @When I scroll :selector into view
   *
   * @param string $selector Allowed selectors: #id, .className, //xpath.
   *
   * @throws Exception
   */
  public function scrollIntoView($selector) {
    $locator = $selector;

    switch ($locator) {
      // XPath selector.
      case $locator[0] === '/':
        $function = <<<JS
          (function(){
            var elem = document.evaluate($selector, document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue;
            elem.scrollIntoView({ behavior: "instant", block: "center", inline: "start" });
          })()
        JS;
        break;
      // Id selector.
      case $locator[0] === '#':
        $selector = substr($selector, 1);
        $function = <<<JS
          (function(){
            var elem = document.getElementById("$selector");
            elem.scrollIntoView({ behavior: "instant", block: "center", inline: "start" });
          })()
        JS;
        break;
      // Class name selector.
      case $locator[0] === '.':
        $selector = substr($selector, 1);
        $function = <<<JS
          (function(){
            var elem = document.getElementsByClassName("$selector");
            elem[0].scrollIntoView({ behavior: "instant", block: "center", inline: "start" });
          })()
        JS;
        break;
      case (bool) $locator:
        $function = <<<JS
          (function(){
            let elems = document.querySelectorAll("$locator");
            elems[0].scrollIntoView({ behavior: "instant", block: "start", inline: "start" });
          })()
        JS;
        break;
      default:
        throw new Exception(__METHOD__ . ' Couldn\'t find selector: ' . $selector . ' - Allowed selectors: #id, .className, //xpath');
    }

    try {
      $this->getSession()->executeScript($function);
    }
    catch (Exception $e) {
      throw new Exception(__METHOD__ . ' failed');
    }
  }

  /**
   * Check if X number of elements exist on the page.
   *
   * @Then I should see that :number of :selector elements exist
   *
   * @param int $number
   * @param string $selector
   *
   * @throws Exception
   */
  public function xNumberElementsExist($number, $selector) {
    $function = <<<JS
      (function(){
        var elem = document.querySelectorAll("$selector");
        return elem.length === $number;
      })()
    JS;

    if ($this->getSession()->evaluateScript($function)) {
      return;
    }

    throw new Exception(__METHOD__ . ' failed');
  }

  /**
   * Waits a while, for debugging.
   *
   * @Then I wait for :seconds second(s)
   * @When I wait :seconds second(s)
   *
   * @param int $seconds How long to wait.
   */
  public function wait($seconds) {
    sleep($seconds);
  }

  /**
   * Check if X class have X style with X value.
   *
   * @Then I should see that :style with :value is in :selector class
   *
   * @param string $selector
   * @param string $style
   * @param string $value
   *
   * @return null
   * @throws Exception
   */
  public function cssStyleExists(string $selector, string $style, string $value) {
    $function = <<<JS
      (function(){
        var item = document.querySelector('$selector');
        var styles = window.getComputedStyle(item);
        var styleValue = styles.getPropertyValue('$style');

        if (styleValue.trim() === '$value') {
          return true;
        } else {
          return false;
        }
      })()
    JS;

    try {
      return $this->getSession()->executeScript($function);
    }
    catch (Exception $e) {
      throw new Exception(__METHOD__ . ' failed');
    }
  }

  /**
   * Grab the value of how many pages there are in pagination.
   *
   * @Given I store the last pagination page number
   *
   * @return void
   * @throws Exception
   */
  public function getPaginatedPages() {
    $lastPageElement = $this->getSession()
      ->getPage()
      ->find('css', '.page-item:nth-last-child(2) > .page-link');

    if (NULL === $lastPageElement) {
      throw new Exception('Last pagination element not found.');
    }

    $this->pagedNumbers = (int) filter_var($lastPageElement->getText(), FILTER_SANITIZE_NUMBER_INT);
    echo $this->pagedNumbers;
  }

  /**
   * Compare the number of paginated pages after applying a filter.
   *
   * @Then Check the current pagination is :comparison than the previous pagination
   *
   * @param string $comparison less|greater
   *
   * @throws Exception
   */
  public function checkPaginationAfterFilter(string $comparison) {
    $lastPageElement = $this->getSession()
      ->getPage()
      ->find('css', '.page-item:nth-last-child(2) > .page-link');

    if (NULL === $lastPageElement) {
      throw new Exception('Last pagination element not found.');
    }

    $currentPageNumber = (int) filter_var($lastPageElement->getText(), FILTER_SANITIZE_NUMBER_INT);

    if ($comparison === 'less' && $currentPageNumber >= $this->pagedNumbers) {
      throw new Exception("Expected less than the stored value {$this->pagedNumbers}, but found $currentPageNumber");
    }
    elseif ($comparison === 'greater' && $currentPageNumber <= $this->pagedNumbers) {
      throw new Exception("Expected greater than the stored value {$this->pagedNumbers}, but found $currentPageNumber");
    }

    echo $currentPageNumber;
  }
}
