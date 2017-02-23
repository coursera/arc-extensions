<?php

final class ArcanistESLintLinter extends ArcanistExternalLinter {

    private $eslintenv;
    private $eslintconfig;
    private $eslintignore;

    public function getInfoName() {
        return 'ESLint';
    }

    public function getInfoURI() {
        return 'https://www.eslint.org';
    }

    public function getRuleDocumentationURI($ruleId) {
        return $this->getInfoURI().'/docs/rules/'.$ruleId;
    }

    public function getInfoDescription() {
        return pht('ESLint is a linter for JavaScript source files.');
    }

    public function getVersion() {
        $output = exec('eslint --version');

        if (strpos($output, 'command not found') !== false) {
            return false;
        }

        return $output;
    }

    public function getLinterName() {
        return 'ESLINT';
    }

    public function getLinterConfigurationName() {
        return 'eslint';
    }

    public function getDefaultBinary() {
        return 'eslint';
    }

    public function getInstallInstructions() {
        return pht('Install ESLint using `%s`.', 'npm install -g eslint eslint-plugin-react');
    }

    public function getMandatoryFlags() {
        $options = array();

        $options[] = '--format='.dirname(realpath(__FILE__)).'/eslintJsonFormat.js';

        return $options;
    }

    public function getLintSeverityMap() {
        return array(
            2 => ArcanistLintSeverity::SEVERITY_ERROR,
            1 => ArcanistLintSeverity::SEVERITY_WARNING
        );
    }

    protected function getDefaultMessageSeverity($code) {
      return NULL;
    }

    protected function getESLintMessageSeverity($code, $outputtedSeverity) {
      $severityWithCode = $this->getLintMessageSeverity($code);

      if (!is_null($severityWithCode)) {
        return $severityWithCode;
      }

      // did not overwrite, output the original severity
      return $outputtedSeverity === 'error' ?
        ArcanistLintSeverity::SEVERITY_ERROR :
        ArcanistLintSeverity::SEVERITY_WARNING;
    }

    protected function parseLinterOutput($path, $err, $stdout, $stderr) {
        try {
            $json = phutil_json_decode($stdout);
            // Since arc only lints one file at at time, we only need the first result
            $results = idx(idx($json, 'results')[0], 'messages');
        } catch (PhutilJSONParserException $ex) {
            // Something went wrong and we can't decode the output. Exit abnormally.
            if (empty($stdout)) {
              throw new PhutilProxyException(
                  pht('ESLint threw an error: '.$stderr),
                  $ex);
            } else {
              throw new PhutilProxyException(
                  pht('ESLint returned unparseable output: '.$stdout),
                  $ex);
            }
        }
        $messages = array();
        foreach ($results as $result) {
            $ruleId = idx($result, 'ruleId');

            // Only rules built into eslint are guaranteed to have a rule documentation URI.
            if (strpos($ruleId, '/') !== FALSE) {
              $documentation = '';
            } else {
              $documentation = "\r\nSee documentation at ".$this->getRuleDocumentationURI($ruleId);
            }

            $description = idx($result, 'message').$documentation;
            $message = new ArcanistLintMessage();
            $message->setChar(idx($result, 'column'));
            $message->setCode($ruleId);
            $message->setDescription($description);
            $message->setLine(idx($result, 'line'));
            $message->setName('ESLint.'.$ruleId);
            $message->setPath($path);
            $message->setSeverity($this->getESLintMessageSeverity(idx($result, 'ruleId'), idx($result, 'severity')));

            $messages[] = $message;
        }

        return $messages;
    }

}
