<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Service de cryptage pour le token

 * @author Alexis BOTT, Gaël GREBERT
 */
class CryptageService
{

  private $container;

  public function __construct(
    ContainerInterface $container
  ) {
    $this->container = $container;
  }

  /**
   * crypte et décrypte une string
   *
   * @param string $string
   * @param boolean $encrypt
   *
   * @return string décryptée
   *
   */
  public function encryptDecrypt($string, $encrypt = false)
  {
    $output = false;
    $encrypt_method = "AES-256-CBC";
    $secret_key = $this->container->getParameter('secret_key');
    $secret_iv = 'EgArEaMeDkO';
    // hash
    $key = hash('sha256', $secret_key);
    // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
    $iv = substr(hash('sha256', $secret_iv), 0, 16);
    if ($encrypt) {
      $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
      $output = base64_encode($output);
    } else {
      $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
    }
    return $output;
  }

  /**
   * permet de lancer un cryptage ou un décryptage de façon multiple
   *
   * @param string $string
   * @param integer $timeOfCrypt
   * @param boolean $encrypt
   *
   * @return string décryptée
   *
   */
  public function multipleEncryptDecrypt($string, $timeOfCrypt = 1, $encrypt = false)
  {
    $output = $string;
    for ($vI = 0; $vI < $timeOfCrypt; $vI++) {
      $output = $this->encryptDecrypt($output, $encrypt);
    }
    return $output;
  }
}
