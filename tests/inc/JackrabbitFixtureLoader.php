<?php
require_once __DIR__.'/../phpcr-api/inc/FixtureLoaderInterface.php';

/**
 * Handles basic importing and exporting of fixtures trough
 * the java binary jack.jar
 *
 * Connection parameters for jackrabbit have to be set in the $GLOBALS array (i.e. in phpunit.xml)
 *     <php>
 *      <var name="jackrabbit.uri" value="http://localhost:8080/server" />
 *      <var name="phpcr.user" value="admin" />
 *      <var name="phpcr.pass" value="admin" />
 *      <var name="phpcr.workspace" value="tests" />
 *    </php>
 */
class JackrabbitFixtureLoader implements \PHPCR\Test\FixtureLoaderInterface
{

    protected $fixturePath;
    protected $jar;

    /**
     * @param string $fixturePath path to the fixtures directory. defaults to __DIR__ . '/suite/fixtures/'
     * @param string $jackjar path to the jar file for import-export. defaults to __DIR__ . '/bin/jack.jar'
     */
    public function __construct($fixturePath = null, $jackjar = null)
    {
        if (is_null($fixturePath)) {
            $this->fixturePath = __DIR__ . '/../phpcr-api/fixtures/';
        } else {
            $this->fixturePath = $fixturePath;
        }
        if (!is_dir($this->fixturePath)) {
            throw new Exception('Not a valid directory: ' . $this->fixturePath);
        }

        if (is_null($jackjar)) {
            $this->jar = __DIR__ . '/../bin/jack.jar';
        } else {
            $this->jar = $jackjar;
        }
        if (!file_exists($this->jar)) {
            throw new Exception('jack.jar not found at: ' . $this->jar);
        }
    }

    private function getArguments()
    {
        $args = array(
            'jackrabbit.uri' => 'storage',
            'phpcr.user' => 'username',
            'phpcr.pass' => 'password',
            'phpcr.workspace' => 'workspace',
            'phpcr.basepath' => 'repository-base-xpath',
        );
        $opts = "";
        foreach ($args as $arg => $newArg) {
            if (isset($GLOBALS[$arg])) {
                if ($opts != "") {
                    $opts .= " ";
                }
                $opts .= " " . $newArg . "=" . $GLOBALS[$arg];
            }
        }
        return $opts;
    }

    /**
     * import the jcr dump into jackrabbit
     * @param string $fixture path to the fixture file, relative to fixturePath
     * @throws Exception if anything fails
     */
    public function import($fixture)
    {
        $fixture = $this->fixturePath . $fixture . ".xml";
        if (!is_readable($fixture)) {
            throw new Exception('Fixture not found at: ' . $fixture);
        }

        //TODO fix the stderr redirect which doesn't work properly
        exec('java -jar ' . $this->jar . ' import ' . $fixture . " " . $this->getArguments() . " 2>&1", $output, $ret);
        if ($ret !== 0) {
            $msg = '';
            foreach ($output as $line) {
                $msg .= $line . "\n";
            }
            throw new Exception($msg);
        }
        return true;
    }

    /**
     * export a document view to a file
     *
     * TODO: add path parameter so you can export just content parts (exporting / exports jcr:system too, which is huge and ugly)
     * @param $file path to the file, relative to fixturePath. the file may not yet exist
     * @throws Exception if the file already exists or if the export fails
     */
    public function exportdocument($file)
    {
        $fixture = $this->fixturePath . $file;
        if (is_readable($fixture)) {
            throw new Exception('File existing: ' . $fixture);
        }

        //TODO fix the stderr redirect which doesn't work properly
        exec('java -jar ' . $this->jar . ' exportdocument ' . $fixture . " " . $this->getArguments() . " 2>&1", $output, $ret);
        if ($ret !== 0) {
            $msg = '';
            foreach ($output as $line) {
                $msg .= $line . "\n";
            }
            throw new Exception($msg);
        }
        return true;
    }
}
