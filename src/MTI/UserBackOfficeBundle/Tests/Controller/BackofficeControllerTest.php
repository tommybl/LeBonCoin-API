<?php

namespace MTI\UserBackOfficeBundle\Tests\Controller;

use MTI\UserBackOfficeBundle\Controller\BackofficeController;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BackofficeControllerTest extends WebTestCase
{
	private $em;

    public function setUp()
    {
        static::$kernel = static::createKernel();
        static::$kernel->boot();
        $this->em = static::$kernel->getContainer()->get('doctrine.orm.entity_manager');
    }

    public function testShow()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/show/10');

        $this->assertTrue($crawler->filter('html:contains("pierre")')->count() > 0);
    }

    public function testCreate()
    {

    	$client = static::createClient();
    	$client->request(
    		'POST', 
    		'/addtest', 
    		array('_username' => 'test','_lastname' => 'test','_password' => 'test','_email' => 'test')
    	);

    	$client->insulate();

       	$repository = $this->em
		    ->getRepository('MTIUserBackOfficeBundle:Profile');

		$query = $repository->createQueryBuilder('p')
		    ->orderBy('p.id', 'DESC')
		    ->getQuery();

		$profiles = $query->getResult();

		if (!$profiles) {
		    throw $this->createNotFoundException(
		        'Aucun profil trouvé pour ces clés d\'api'
		    );
		}
		else
		    $profile = $profiles[0];

		$this->assertEquals('test', $profile->getUsername());
    }
     
    public function testUpdate()
    {
    	$client = static::createClient();
    	$client->request(
    		'POST', 
    		'/update', 
    		array('offer' => 3,'idUser' => 10)
    	);

    	$client->insulate();

    	$user = $this->em
	        ->getRepository('MTIUserBackOfficeBundle:Profile')
	        ->find(10);

	    $this->assertEquals(3, $user->getSubscribe());
    }

    public function testJson()
    {
    	$client = static::createClient();

        $crawler = $client->request('GET', '/testjson');

    	$this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'));
    }

    public function testAddCall()
    {
    	$query2 = $this->em->createQuery(
		    'SELECT count(call)
		    FROM MTIUserBackOfficeBundle:Call call
		    WHERE call.userid = :user'
		)->setParameter('user', 10);

	    $oldcount = $query2->getSingleResult();

    	$client = static::createClient();
    	$client->request(
    		'POST', 
    		'/addcall', 
    		array('idUser' => 10)
    	);

    	$client->insulate();

    	$query3 = $this->em->createQuery(
		    'SELECT count(call)
		    FROM MTIUserBackOfficeBundle:Call call
		    WHERE call.userid = :user'
		)->setParameter('user', 10);

	    $count = $query3->getSingleResult();

	    $this->assertEquals($oldcount, $count);
    }

    public function testGetAnnonce()
    {
    	$client = static::createClient();

        $crawler = $client->request('GET', '/leboncoin/get/ads/820098496/?token=TWpBMk1qUXpOakV4Ok1qQTNNVEUxTlRVNE53PT0=');

    	$this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'));
    }
}