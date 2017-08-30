<?php
namespace JasperPHP;
/**
 * Class JasperPHP
 * @package JasperPHP
 */
class JasperPHP {

    /**
     * @var string
     */
    protected $command;

    /**
     * @var string
     */
    protected $executable;

    /**
     * @var string
     */
    protected $path_executable;

    /**
     * @var bool
     */
    protected $windows;

    /**
     * @var array
     */
    protected $formats = ['pdf', 'rtf', 'xls', 'xlsx', 'docx', 'odt', 'ods', 'pptx', 'csv', 'html', 'xhtml', 'xml', 'jrprint'];

    /**
     * JasperPHP constructor
     */
    public function __construct()
    {
        $this->executable = 'jasperstarter';
        $this->path_executable = realpath(__DIR__ . '/../bin/jasperstarter/bin');
        $this->windows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? true : false;
    }

    /**
     * @param $input_file
     * @param bool $output_file
     * @return $this
     * @throws Exception\InvalidInputFile
     */
    public function compile($input_file, $output_file = false)
    {
        if (!$input_file) {
            throw new \JasperPHP\Exception\InvalidInputFile();
        }

        $this->command = $this->windows ? $this->executable : $this->path_executable . DIRECTORY_SEPARATOR . $this->executable;
        $this->command .= ' compile ';

        $dirname = dirname($input_file);
        chdir($dirname);
        $input_file = basename($input_file);

        $this->command .= "\"$input_file\"";

        if ($output_file !== false) {
            $this->command .= ' -o ' . "\"$output_file\"";
        }

        return $this;
    }


    /**
     * @param $input_file
     * @param bool $output_file
     * @param array $options
     * @return $this
     * @throws Exception\InvalidInputFile
     * @throws Exception\InvalidFormat
     */
    public function process($input_file, $output_file = false, $options = [])
    {
        $options = $this->parseProcessOptions($options);
        if (!$input_file) {
            throw new \JasperPHP\Exception\InvalidInputFile();
        }
        $this->validateFormat($options['format']);

        $this->command = $this->windows ? $this->executable : $this->path_executable . DIRECTORY_SEPARATOR. $this->executable;
        if ($options['locale']) {
            $this->command .= " --locale {$options['locale']}";
        }

        $dirname = dirname($input_file);
        chdir($dirname);
        $input_file = basename($input_file);

        $this->command .= ' process ';
        $this->command .= "\"$input_file\"";
        if ($output_file !== false) {
            $this->command .= ' -o ' . "\"$output_file\"";
        }

        $this->command .= ' -f ' . join(' ', $options['format']);
        if ($options['params']) {
            $this->command .= ' -P ';
            foreach ($options['params'] as $key => $value) {
                $this->command .= " " . $key . '="' . $value . '" ' . " ";
            }
        }

        if ($options['db_connection']) {
            $mapDbParams = [
                'driver' => '-t',
                'username' => '-u',
                'password' => '-p',
                'host' => '-H',
                'database' => '-n',
                'port' => '--db-port',
                'jdbc_driver' => '--db-driver',
                'jdbc_url' => '--db-url',
                'jdbc_dir' => '--jdbc-dir',
                'db_sid' => '-db-sid',
                'xml_xpath' => '--xml-xpath',
                'data_file' => '--data-file',
                'json_query' => '--json-query'
            ];

            foreach ($options['db_connection'] as $key => $value) {
                $this->command .= " {$mapDbParams[$key]} {$value}";
            }
        }

        return $this;
    }

    /**
     *
     * @param $options
     * @return array
     */
    protected function parseProcessOptions($options)
    {
        $defaultOptions = [
            'format' => ['pdf'],
            'params' => [],
            'locale' => false,
            'db_connection' => []
        ];

        return array_merge($defaultOptions, $options);
    }

    /**
     * @param $format
     * @throws Exception\InvalidFormat
     */
    protected function validateFormat($format)
    {
        if (!is_array($format)) {
            $format = [$format];
        }
        foreach ($format as $value) {
            if (!in_array($value, $this->formats)) {
                throw new \JasperPHP\Exception\InvalidFormat();
            }
        }
    }

    /**
     * @param $input_file
     * @return $this
     * @throws \Exception
     */
    public function listParameters($input_file)
    {
        if (!$input_file) {
            throw new \JasperPHP\Exception\InvalidInputFile();
        }

        $this->command = $this->windows ? $this->executable : './' . $this->executable;
        $this->command .= ' list_parameters ';
        $this->command .= "\"$input_file\"";

        return $this;
    }

    /**
     * @param bool $user
     * @return mixed
     * @throws Exception\InvalidCommandExecutable
     * @throws Exception\InvalidResourceDirectory
     * @throws Exception\ErrorCommandExecutable
     */
    public function execute($user = false)
    {
        $this->validateExecute();
        $this->addUserToCommand($user);

        $return_var = 0;

        $proc = proc_open($this->command,[
            1 => ['pipe','w'],
            2 => ['pipe','w'],
        ],$pipes);

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        $return_var = proc_get_status($proc)['exitcode'];

        proc_close($proc);

        if ($return_var !== 0) {
            throw new \JasperPHP\Exception\ErrorCommandExecutable($stderr);
        }

        return explode("\n", $stdout);
    }

    /**
     * @return string
     */
    public function output()
    {
        return $this->command;
    }

    /**
     * @param $user
     */
    protected function addUserToCommand($user)
    {
        if ($user && !$this->windows) {
            $this->command = 'su -u ' . $user . " -c \"" . $this->command . "\"";
        }
    }

    /**
     * @throws Exception\InvalidCommandExecutable
     * @throws Exception\InvalidResourceDirectory
     */
    protected function validateExecute()
    {
        if (!$this->command) {
            throw new \JasperPHP\Exception\InvalidCommandExecutable();
        }
        if (!is_dir($this->path_executable)) {
            throw new \JasperPHP\Exception\InvalidResourceDirectory();
        }

    }

}
