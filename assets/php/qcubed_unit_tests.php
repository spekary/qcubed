<?php

/* This file is the file to point the browser to to launch unit tests */

$__CONFIG_ONLY__ = true;
require('./qcubed.inc.php');

require_once(__EXTERNAL_LIBRARIES__ . '/lastcraft/simpletest/unit_tester.php');
require_once(__EXTERNAL_LIBRARIES__ . '/lastcraft/simpletest/reporter.php');

$__CONFIG_ONLY__ = false;
require('./qcubed.inc.php');

// not using QCubed error handler for unit tests - using the SimpleTest one instead
restore_error_handler();

require_once(__QCUBED_CORE__ . '/tests/qcubed-unit/QUnitTestCaseBase.php');
require_once(__QCUBED_CORE__ . '/tests/qcubed-unit/QTestControl.class.php');

class QHtmlReporter extends HtmlReporter {
	function paintMethodStart($test_name) {
		$tempBreadcrumb = $this->getTestList();
		array_shift($tempBreadcrumb);
		$breadcrumb = implode("-&gt;", $tempBreadcrumb);

		echo "<b>{$breadcrumb} > {$test_name}</b><br />";
	}

	function paintMethodEnd($test_name) {
		echo "<br />";
	}

	function paintPass($message) {
		parent::paintPass($message);

		$messageWithoutTrace = trim(substr($message, 0, strpos($message, " at [")));
		if (strlen($messageWithoutTrace) == 0) {
			// don't show empty messages (they appear if debugging is conditionally disabled)
			return;
		}

		print "<span class=\"pass\">Pass</span>: ";

		print "{$messageWithoutTrace}<br />\n";
	}
}

class QTestForm extends QForm {
	public $ctlTest;
	public $pnlOutput;

	protected function Form_Create() {
		$this->ctlTest = new QTestControl($this);
		$this->pnlOutput = new QPanel($this, 'outputPanel');
		
		$t1 = new QJsTimer($this, 200, false, true, 'timer1');
		$t1->AddAction(new QTimerExpiredEvent(), new QAjaxAction ('preTest'));
		$t2 = new QJsTimer($this, 201, false, true, 'timer2');
		$t2->AddAction(new QTimerExpiredEvent(), new QAjaxAction ('preTest2'));
		$t3 = new QJsTimer($this, 400, false, true, 'timer3');
		$t3->AddAction(new QTimerExpiredEvent(), new QServerAction ('runTests'));
	}
	
	public function preTest() {
		$this->ctlTest->savedValue1 = 2;	// for test in QControlBaseTests
	}
	
	public function preTest2() {
		$this->ctlTest->savedValue2 = $this->ctlTest->savedValue1;	// for test in QControlBaseTests
	}
	
	
	public function runTests() {
		
		$filesToSkip = array(
			"QUnitTestCaseBase.php"
			, "QTestForm.tpl.php"
			, "QTestControl.class.php"
		);

		$arrFiles = QFolder::listFilesInFolder(__QCUBED_CORE__ . '/tests/qcubed-unit/');
		$arrTests = array();
		foreach ($arrFiles as $filename) {
			if (!in_array($filename, $filesToSkip)) {
				require_once(__QCUBED_CORE__ . '/tests/qcubed-unit/' . $filename);
				$arrTests[] = str_replace(".php", "", $filename);
			}
		}

		$suite = new TestSuite('QCubed ' . QCUBED_VERSION_NUMBER_ONLY . ' Unit Tests - SimpleTest ' . SimpleTest::getVersion());
		foreach ($arrTests as $className) {
			$suite->add(new $className($this));
		}
		$suite->run(new QHtmlReporter());
	}
}

QTestForm::Run('QTestForm', __QCUBED_CORE__ . "/tests/qcubed-unit/QTestForm.tpl.php");

?>
