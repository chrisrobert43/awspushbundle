<?php
namespace mcfedr\AWSPushBundle\Command;

use Aws\Sns\SnsClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EnableAllCommand extends Command
{
    /**
     * @var SnsClient
     */
    private $sns;

    /**
     * @var array
     */
    private $arns;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param SnsClient $sns
     * @param array $arns
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(SnsClient $sns, $arns, LoggerInterface $logger)
    {
        parent::__construct();

        $this->sns = $sns;
        $this->arns = $arns;
        $this->logger = $logger;
    }


    protected function configure()
    {
        $this
            ->setName('mcfedr:aws:enable')
            ->setDescription('Reenable all devices');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->arns as $platform => $arn) {
            $this->logger->info("Enabling $platform");
            $this->enablePlatform($platform);
        }
    }

    /**
     * Enable all devices registered on platform
     *
     * @param string $platform
     */
    private function enablePlatform($platform)
    {
        foreach ($this->sns->getListEndpointsByPlatformApplicationIterator(
                     [
                         'PlatformApplicationArn' => $this->arns[$platform]
                     ]
                 ) as $endpoint) {
            if ($endpoint['Attributes']['Enabled'] == "false") {
                try {
                    $this->sns->setEndpointAttributes(
                        [
                            'EndpointArn' => $endpoint['EndpointArn'],
                            'Attributes' => [
                                'Enabled' => "true"
                            ]
                        ]
                    );
                    $this->logger->info("Enabled {$endpoint['EndpointArn']}");
                } catch (\Exception $e) {
                    $this->logger->error(
                        "Failed to push set attributes on {$endpoint['EndpointArn']}",
                        [
                            'exception' => $e,
                            'endpoint' => $endpoint
                        ]
                    );
                }
            }
        }
    }
}
