<?php

namespace MTI\LeBonCoinBundle\Tools;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use MTI\UserBackOfficeBundle\Entity\Profile;
use MTI\UserBackOfficeBundle\Entity\Call;
use MTI\UserBackOfficeBundle\Entity\Cache;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\Query\ResultSetMapping;

class CheckUserCall extends Controller
{
	protected  $doctrine;

    public function __construct($doctrine) {
        $this->doctrine = $doctrine;
    }

	public function check($token)
	{
		if ($token == null) return "errorTokenMissing";
		$tokendecode = base64_decode($token);
		$pos = strpos(':', $tokendecode);
		if($pos > 0)
			return "errorBadToken";
		else
		{
			$keys = explode(':',$tokendecode);
			$apipublickey = $keys[0];
			$apisecretkey = $keys[1];

	        $em = $this->doctrine->getManager();

			$repository = $this->doctrine->getRepository('MTIUserBackOfficeBundle:Profile');

			$query = $repository->createQueryBuilder('p')
			    ->where('p.publicapikey = :public')
			    ->andWhere('p.secretapikey = :secret')
			    ->setParameters(array(
			    'public' => $apipublickey,
			    'secret'  => $apisecretkey
				))
			    ->getQuery();

			$profile = $query->getResult();

		    if (!$profile) {
		        return "errorNoAccount";
		    }
		    else
		    	$profile = $profile[0];

		    $query2 = $em->createQuery(
		    'SELECT count(call)
		    FROM MTIUserBackOfficeBundle:Call call
		    WHERE call.userid = :user'
			)->setParameter('user', $profile->getId());

			$count = $query2->getSingleResult();

			if ($count[1] < $this->giveLimitation($profile->getSubscribe()))
				return $profile;
			else
				return "errorLimit";
		}
	}

	public function checkCache($request_url)
	{
		$em = $this->doctrine->getManager();

		$callCacheQuery = $em->createQuery(
		    'SELECT cache.id, cache.request, cache.response, cache.created
		    FROM MTIUserBackOfficeBundle:Cache cache
		    WHERE cache.request = :request_url'
		)->setParameter('request_url', $request_url);

		$callCache = $callCacheQuery->getResult();
		/*echo '<br>--------------------------<br>';
		print_r($callCache);
		echo '<br>--------------------------<br>';*/
		if (count($callCache) > 0) {
			$date_now = new \DateTime();
			$date_query = $callCache[0]['created'];
			if (intval($date_query->diff($date_now)->format('%i')) > 5) {
				$delCacheQuery = $em->createQuery(
				    'DELETE FROM MTIUserBackOfficeBundle:Cache cache
				    WHERE cache.id = :cache_id'
				)->setParameter('cache_id', $callCache[0]['id']);
				$delCacheQuery->getResult();
				return false;
			}
			return $callCache[0]['response'];
		}
		return false;
	}

	public function addChache($request_url, $response_json)
	{
		$cache = new Cache();

		$cache->setRequest($request_url.'');
		$cache->setResponse($response_json.'');

		$em = $this->doctrine->getManager();
	    $em->persist($cache);
	    $em->flush();
	}

	public function giveLimitation($type)
	{
		switch ($type) {
			case 1:
				return 50;
				break;
			case 2:
				return 200;
				break;
			case 3:
				return 500;
				break;
			case 0:
				return 10000000;
				break;
			default:
				return 50;
				break;
		}
	}
}