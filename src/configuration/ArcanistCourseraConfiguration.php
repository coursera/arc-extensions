<?php

final class ArcanistCourseraConfiguration extends ArcanistConfiguration {  
  // override this to return a custom workflow
  public function buildWorkflow($command) {
    if ($command == '--help') {
      // Special-case "arc --help" to behave like "arc help" instead of telling
      // you to type "arc help" without being helpful.
      $command = 'help';
    } else if ($command == '--version') {
      // Special-case "arc --version" to behave like "arc version".
      $command = 'version';
    }

    if ($command == 'lint') {
        $workflow = idx($this->buildAllWorkflows(), "coursera-lint");
    } else {
        $workflow = idx($this->buildAllWorkflows(), $command);
    }

    if (!$workflow) {
      return null;
    }
    return clone $workflow;
  }
}
