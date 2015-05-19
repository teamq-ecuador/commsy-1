<?php

namespace CommSy\LegacyBundle\Kernel;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class LegacyKernel
 *
 * Handles requests with legacy CommSy application by requiring legacy application "commsy.php front controller".
 * Captures legacy CommSy application output with output buffering and wraps it in a Response object.
 */
class LegacyKernel implements HttpKernelInterface
{
	/**
	 * Path to the legacy application "commsy_legacy.php front controller"
	 * @var String
	 */
	private $legacyAppPath;

	/**
	 * ContainerInterface
	 * @var ContainerInterface
	 */
	private $container;

	/**
	 * @param String             $legacyAppPath
	 * @param ContainerInterface $container
	 */
	public function __construct($legacyAppPath, ContainerInterface $container)
	{
		$this->legacyAppPath = $legacyAppPath;
		$this->container = $container;
	}

	/**
	 * {@inheritdoc}
	 */
	public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true)
	{
		if (!file_exists($this->legacyAppPath)) {
			throw new \RuntimeException(
				sprintf('Invalid legacy app path (%s), unable to handle request', $this->legacyAppPath));
		}

		// Assign container to local variable so it can be used in legacy app
		$container = $this->container;

		// Request is already in a local variable $request
		
		// Start output buffering to capture output generated by legacy app
		ob_start();

		// Change current working dir to legacy base dir for relative includes etc. in the legacy app
		$legacyDir = dirname($this->legacyAppPath);
		chdir($legacyDir);

		require_once($this->legacyAppPath);

		$legacyOutput = ob_get_clean();
		$response = new Response($legacyOutput);

		return $response;
	}
}