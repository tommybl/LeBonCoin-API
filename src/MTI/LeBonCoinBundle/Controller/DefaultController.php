<?php

namespace MTI\LeBonCoinBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use MTI\LeBonCoinBundle\Tools\CheckUserCall;
use MTI\LeBonCoinBundle\Tools\ParsingTools;

define("COOKIE_FILE", "cookie.txt");

class DefaultController extends Controller
{

    public function indexAction($name)
    {
        $check = new CheckUserCall($this->getDoctrine());
        var_dump($check->check());
        return $this->render('MTILeBonCoinBundle:Default:index.html.twig', array('name' => $name));
    }

    public function offersAction(Request $request)
    {
        $token_url = $request->query->get('token');
        $checkUserCall = new CheckUserCall($this->getDoctrine());
        $profile = $checkUserCall->check($token_url);

        if (is_string($profile)) {
            $response = new Response();
            if ($profile == 'errorTokenMissing') $response->setContent(json_encode(array('error' => 'Please provide your user token')));
            else if ($profile == 'errorBadToken') $response->setContent(json_encode(array('error' => 'Please provide a token with valid format')));
            else if ($profile == 'errorNoAccount') $response->setContent(json_encode(array('error' => 'No matching account for this token')));
            else $response->setContent(json_encode(array('error' => 'You have reach your request limit')));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        $cache_url = $request->query->get('cache');
        $region_url = (!in_array($request->query->get('region'), ParsingTools::$regions_map)) ? null : $request->query->get('region');
        $category_url = (!in_array($request->query->get('category'), ParsingTools::$categories_map)) ? "annonces" : $request->query->get('category');
        $towns_url = $request->query->get('towns');
        $type_url = $request->query->get('type');
        $query_url = $request->query->get('query');
        $pmin_url = $request->query->get('pmin');
        $pmax_url = $request->query->get('pmax');
        $page_url = $request->query->get('page');
        if ($type_url != null && $type_url == 'ind') $type_url = 'p';
        else if ($type_url != null && $type_url == 'pro') $type_url = 'c';
        else $type_url = 'a';
        $sort_url = ($request->query->get('sort') == "price") ? 1 : 0;
        $request_url = 'http://www.leboncoin.fr/'.$category_url.'/offres/'.(($region_url == null) ? '' : $region_url.'/')."?";
        if ($towns_url != null) $request_url .= "&location=".$towns_url;
        if ($sort_url != null) $request_url .= "&sp=".$sort_url;
        if ($type_url != null) $request_url .= "&f=".$type_url;
        if ($query_url != null) $request_url .= "&q=".$query_url;
        if ($pmin_url != null) $request_url .= "&ps=".$pmin_url;
        if ($pmax_url != null) $request_url .= "&pe=".$pmax_url;
        if ($page_url != null) $request_url .= "&o=".$page_url;
        //echo $request_url;

        $request_type = ($type_url == 'p') ? 1 : (($type_url == 'c') ? 2 : 0);

        if ($cache_url != "false") {
            $has_cache = $checkUserCall->checkCache($request_url);
            if ($has_cache != false) {
                $response = new Response();
                $response->setContent($has_cache);
                $response->headers->set('Content-Type', 'application/json');
                ParsingTools::addRequest($this, $profile, $request_type, ($region_url == null) ? null : ParsingTools::$regions_match[$region_url], array_search($category_url, ParsingTools::$categories_map));
                return $response;
            }
        }

        $html = file_get_html($request_url);

        if ($html->find('h2[id=result_ad_not_found]', 0) != null) {
            $response = new Response();
            $response->setContent(json_encode(array('error' => 'No results found')));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
        else if ($html->find('h1[id=result_ad_not_found_proaccount]', 0) != null) {
            $response = new Response();
            $response->setContent(json_encode(array('error' => 'No professional results found')));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }


        $onglet_all = $html->find('ul[class=navlist type]', 0)->find('li', 0);
        $onglet_part = $html->find('ul[class=navlist type]', 0)->find('li', 1);
        $onglet_pro = $html->find('ul[class=navlist type]', 0)->find('li', 2);
        $nb_individual = $nb_professional = null;
        if ($onglet_all->class == "selected") {
            $nb_total = $onglet_all->find('b', 0);
            if ($nb_total != null) {
                $nb_total = $nb_total->plaintext;
                $nb_curr = $onglet_all->find('span[class=value]', 0)->plaintext;
            }
            else {
                $nb_total = $onglet_all->find('span[class=value]', 0)->plaintext;
                $nb_curr = null;
            }
            $nb_individual = $onglet_part;
            if ($nb_individual != null) $nb_individual = $nb_individual->find('span[class=value]', 0)->plaintext;
            $nb_professional = $onglet_pro;
            if ($nb_professional != null) $nb_professional = $nb_professional->find('span[class=value]', 0)->plaintext;
        }
        else if ($onglet_part != null && $onglet_part->class == "selected") {
            $nb_total = $onglet_all->plaintext;
            if ($onglet_part->find('b', 0) != null) {
                $nb_curr = $onglet_part->find('span[class=value]', 0)->plaintext;
                $nb_individual = $onglet_part->find('b', 0)->plaintext;
                $nb_professional = $onglet_pro->find('span[class=value]', 0)->plaintext;
            }
            else {
                $nb_curr = null;
                $nb_individual = $onglet_part->find('span[class=value]', 0)->plaintext;
                $nb_professional = $onglet_pro->find('span[class=value]', 0)->plaintext;
            }
        }
        else if ($onglet_pro != null && $onglet_pro->class == "selected") {
            $nb_total = $onglet_all->plaintext;
            if ($onglet_pro->find('b', 0) != null) {
                $nb_curr = $onglet_pro->find('span[class=value]', 0)->plaintext;
                $nb_individual = $onglet_part->find('span[class=value]', 0)->plaintext;
                $nb_professional = $onglet_pro->find('b', 0)->plaintext;
            }
            else {
                $nb_curr = null;
                $nb_individual = $onglet_part->find('span[class=value]', 0)->plaintext;
                $nb_professional = $onglet_pro->find('span[class=value]', 0)->plaintext;
            }
        }


        $curr_page = $html->find('ul[id=paging]', 0);
        if ($curr_page != null) $curr_page = $curr_page->find('li[class=selected]', 0)->plaintext;
        $articles = array();

        foreach($html->find('div[class=list-lbc]', 0)->find('a') as $element) {
            $article_id = explode('.htm', $element->href);
            $article_id = explode('/', $article_id[0]);
            $article_id = $article_id[count($article_id) - 1];
            $locations = explode('/', utf8_encode(trim($element->find('div[class=placement]', 0)->plaintext)));
            if (count($locations) > 1) {
                $town = trim($locations[0]);
                $region = trim($locations[1]);
            }
            else {
                $town = 'all';
                $region = trim($locations[0]);
            }
            $price = $element->find('div[class=price]', 0);
            if ($price != null) $price = preg_replace('/[^0-9]/', '', $price->plaintext);
            $image = $element->find('img', 0);
            $nb_images = 0;
            if ($image != null) {
                $image = $image->src;
                $nb_images = $element->find('div[class=image-and-nb]', 0)->find('div[class=nb]', 0)->find('div[class=value radius]', 0)->plaintext;
            }
            $category = trim($element->find('div[class=category]', 0)->plaintext);

            $article = array(
                'ref' => $article_id,
                'url' => $element->href,
                'title' => utf8_encode($element->title),
                'category' => ($category == "") ? $category_url : $category,
                'region' => $region,
                'town' => $town,
                'price' => $price,
                'image' => $image,
                'nb_images' => $nb_images,
                'date' => $element->find('div[class=date]', 0)->find('div', 0)->plaintext." ".$element->find('div[class=date]', 0)->find('div', 1)->plaintext
            );
            array_push($articles, $article);
        }

        $response = new Response();
        $response_json = json_encode(array(
            'page' => ($curr_page == null) ? null : preg_replace('/[\sa-zA-Z]+/', '', $curr_page),
            'current' => ($nb_curr == null) ? null : preg_replace('/[\sa-zA-Z]+/', '', explode('de', $nb_curr)[0]),
            'total' => preg_replace('/[\sa-zA-Z]+/', '', $nb_total),
            'individual' => ($nb_individual == null) ? null : preg_replace('/[\sa-zA-Z]+/', '', $nb_individual),
            'professional' => ($nb_professional == null) ? null : preg_replace('/[\sa-zA-Z]+/', '', $nb_professional),
            'ads' => 'offers',
            'type' => ($type_url == 'p') ? 'ind' : (($type_url == 'c') ? 'pro' : 'all'),
            'region' => ($region_url == null) ? 'All' : $region_url,
            'town' => $towns_url,
            'category' => $category_url,
            'sort' => ($sort_url == 1) ? 'price' : 'date',
            'query' => ($query_url == null) ? null : $query_url,
            'articles' => $articles

            //'request' => $request->query->get('name')
        ), JSON_UNESCAPED_SLASHES);
        //echo json_decode($response_json);
        $response->setContent($response_json);
        $response->headers->set('Content-Type', 'application/json');

        ParsingTools::addRequest($this, $profile, $request_type, ($region_url == null) ? null : ParsingTools::$regions_match[$region_url], array_search($category_url, ParsingTools::$categories_map));

        if ($cache_url != "false") {
            /*echo '<br>--------------------------<br>';
            echo $response_json;
            echo '<br>--------------------------<br>';*/
            $checkUserCall->addChache($request_url, $response_json);
        }

        return $response;
    }

    public function demandsAction(Request $request)
    {
        $token_url = $request->query->get('token');
        $checkUserCall = new CheckUserCall($this->getDoctrine());
        $profile = $checkUserCall->check($token_url);
        
        if (is_string($profile)) {
            $response = new Response();
            if ($profile == 'errorTokenMissing') $response->setContent(json_encode(array('error' => 'Please provide your user token')));
            else if ($profile == 'errorBadToken') $response->setContent(json_encode(array('error' => 'Please provide a token with valid format')));
            else if ($profile == 'errorNoAccount') $response->setContent(json_encode(array('error' => 'No matching account for this token')));
            else $response->setContent(json_encode(array('error' => 'You have reach your request limit')));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        $cache_url = $request->query->get('cache');
        $region_url = (!in_array($request->query->get('region'), ParsingTools::$regions_map)) ? null : $request->query->get('region');
        $category_url = (!in_array($request->query->get('category'), ParsingTools::$categories_map)) ? "annonces" : $request->query->get('category');
        $towns_url = $request->query->get('towns');
        $type_url = $request->query->get('type');
        $query_url = $request->query->get('query');
        $pmin_url = $request->query->get('pmin');
        $pmax_url = $request->query->get('pmax');
        $page_url = $request->query->get('page');
        if ($type_url != null && $type_url == 'ind') $type_url = 'p';
        else if ($type_url != null && $type_url == 'pro') $type_url = 'c';
        else $type_url = 'a';
        $sort_url = ($request->query->get('sort') == "price") ? 1 : 0;
        $request_url = 'http://www.leboncoin.fr/'.$category_url.'/demandes/'.(($region_url == null) ? '' : $region_url.'/')."?";
        if ($towns_url != null) $request_url .= "&location=".$towns_url;
        if ($sort_url != null) $request_url .= "&sp=".$sort_url;
        if ($type_url != null) $request_url .= "&f=".$type_url;
        if ($query_url != null) $request_url .= "&q=".$query_url;
        if ($pmin_url != null) $request_url .= "&ps=".$pmin_url;
        if ($pmax_url != null) $request_url .= "&pe=".$pmax_url;
        if ($page_url != null) $request_url .= "&o=".$page_url;
        //echo $request_url;

        $request_type = ($type_url == 'p') ? 4 : (($type_url == 'c') ? 5 : 3);

        if ($cache_url != "false") {
            $has_cache = $checkUserCall->checkCache($request_url);
            if ($has_cache != false) {
                $response = new Response();
                $response->setContent($has_cache);
                $response->headers->set('Content-Type', 'application/json');
                ParsingTools::addRequest($this, $profile, $request_type, ($region_url == null) ? null : ParsingTools::$regions_match[$region_url], array_search($category_url, ParsingTools::$categories_map));
                return $response;
            }
        }

        $html = file_get_html($request_url);

        if ($html->find('h2[id=result_ad_not_found]', 0) != null) {
            $response = new Response();
            $response->setContent(json_encode(array('error' => 'No results found')));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
        else if ($html->find('h1[id=result_ad_not_found_proaccount]', 0) != null) {
            $response = new Response();
            $response->setContent(json_encode(array('error' => 'No professional results found')));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }


        $onglet_all = $html->find('ul[class=navlist type]', 0)->find('li', 0);
        $onglet_part = $html->find('ul[class=navlist type]', 0)->find('li', 1);
        $onglet_pro = $html->find('ul[class=navlist type]', 0)->find('li', 2);
        $nb_individual = $nb_professional = null;
        if ($onglet_all->class == "selected") {
            $nb_total = $onglet_all->find('b', 0);
            if ($nb_total != null) {
                $nb_total = $nb_total->plaintext;
                $nb_curr = $onglet_all->find('span[class=value]', 0)->plaintext;
            }
            else {
                $nb_total = $onglet_all->find('span[class=value]', 0)->plaintext;
                $nb_curr = null;
            }
            $nb_individual = $onglet_part;
            if ($nb_individual != null) $nb_individual = $nb_individual->find('span[class=value]', 0)->plaintext;
            $nb_professional = $onglet_pro;
            if ($nb_professional != null) $nb_professional = $nb_professional->find('span[class=value]', 0)->plaintext;
        }
        else if ($onglet_part != null && $onglet_part->class == "selected") {
            $nb_total = $onglet_all->plaintext;
            if ($onglet_part->find('b', 0) != null) {
                $nb_curr = $onglet_part->find('span[class=value]', 0)->plaintext;
                $nb_individual = $onglet_part->find('b', 0)->plaintext;
                $nb_professional = $onglet_pro->find('span[class=value]', 0)->plaintext;
            }
            else {
                $nb_curr = null;
                $nb_individual = $onglet_part->find('span[class=value]', 0)->plaintext;
                $nb_professional = $onglet_pro->find('span[class=value]', 0)->plaintext;
            }
        }
        else if ($onglet_pro != null && $onglet_pro->class == "selected") {
            $nb_total = $onglet_all->plaintext;
            if ($onglet_pro->find('b', 0) != null) {
                $nb_curr = $onglet_pro->find('span[class=value]', 0)->plaintext;
                $nb_individual = $onglet_part->find('span[class=value]', 0)->plaintext;
                $nb_professional = $onglet_pro->find('b', 0)->plaintext;
            }
            else {
                $nb_curr = null;
                $nb_individual = $onglet_part->find('span[class=value]', 0)->plaintext;
                $nb_professional = $onglet_pro->find('span[class=value]', 0)->plaintext;
            }
        }


        $curr_page = $html->find('ul[id=paging]', 0);
        if ($curr_page != null) $curr_page = $curr_page->find('li[class=selected]', 0)->plaintext;
        $articles = array();

        foreach($html->find('div[class=list-lbc]', 0)->find('a') as $element) {
            $article_id = explode('.htm', $element->href);
            $article_id = explode('/', $article_id[0]);
            $article_id = $article_id[count($article_id) - 1];
            $locations = explode('/', utf8_encode(trim($element->find('div[class=placement]', 0)->plaintext)));
            if (count($locations) > 1) {
                $town = trim($locations[0]);
                $region = trim($locations[1]);
            }
            else {
                $town = 'all';
                $region = trim($locations[0]);
            }
            $price = $element->find('div[class=price]', 0);
            if ($price != null) $price = preg_replace('/[^0-9]/', '', $price->plaintext);
            $image = $element->find('img', 0);
            $nb_images = 0;
            if ($image != null) {
                $image = $image->src;
                $nb_images = $element->find('div[class=image-and-nb]', 0)->find('div[class=nb]', 0)->find('div[class=value radius]', 0)->plaintext;
            }
            $category = trim($element->find('div[class=category]', 0)->plaintext);

            $article = array(
                'ref' => $article_id,
                'url' => $element->href,
                'title' => utf8_encode($element->title),
                'category' => ($category == "") ? $category_url : $category,
                'region' => $region,
                'town' => $town,
                'price' => $price,
                'image' => $image,
                'nb_images' => $nb_images,
                'date' => $element->find('div[class=date]', 0)->find('div', 0)->plaintext." ".$element->find('div[class=date]', 0)->find('div', 1)->plaintext
            );
            array_push($articles, $article);
        }

        $response = new Response();
        $response_json = json_encode(array(
            'page' => ($curr_page == null) ? null : preg_replace('/[\sa-zA-Z]+/', '', $curr_page),
            'current' => ($nb_curr == null) ? null : preg_replace('/[\sa-zA-Z]+/', '', explode('de', $nb_curr)[0]),
            'total' => preg_replace('/[\sa-zA-Z]+/', '', $nb_total),
            'individual' => ($nb_individual == null) ? null : preg_replace('/[\sa-zA-Z]+/', '', $nb_individual),
            'professional' => ($nb_professional == null) ? null : preg_replace('/[\sa-zA-Z]+/', '', $nb_professional),
            'ads' => 'demands',
            'type' => ($type_url == 'p') ? 'ind' : (($type_url == 'c') ? 'pro' : 'all'),
            'region' => ($region_url == null) ? 'All' : $region_url,
            'town' => $towns_url,
            'category' => $category_url,
            'sort' => ($sort_url == 1) ? 'price' : 'date',
            'query' => ($query_url == null) ? null : $query_url,
            'articles' => $articles

            //'request' => $request->query->get('name')
        ), JSON_UNESCAPED_SLASHES);
        //echo json_decode($response_json);
        $response->setContent($response_json);
        $response->headers->set('Content-Type', 'application/json');

        ParsingTools::addRequest($this, $profile, $request_type, ($region_url == null) ? null : ParsingTools::$regions_match[$region_url], array_search($category_url, ParsingTools::$categories_map));

        if ($cache_url != "false") {
            /*echo '<br>--------------------------<br>';
            echo $response_json;
            echo '<br>--------------------------<br>';*/
            $checkUserCall->addChache($request_url, $response_json);
        }

        return $response;
    }

    public function adsAction(Request $request, $adID)
    {
        $token_url = $request->query->get('token');
        $checkUserCall = new CheckUserCall($this->getDoctrine());
        $profile = $checkUserCall->check($token_url);
        
        if (is_string($profile)) {
            $response = new Response();
            if ($profile == 'errorTokenMissing') $response->setContent(json_encode(array('error' => 'Please provide your user token')));
            else if ($profile == 'errorBadToken') $response->setContent(json_encode(array('error' => 'Please provide a token with valid format')));
            else if ($profile == 'errorNoAccount') $response->setContent(json_encode(array('error' => 'No matching account for this token')));
            else $response->setContent(json_encode(array('error' => 'You have reach your request limit')));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        $cache_url = $request->query->get('cache');

        $request_url = 'http://www.leboncoin.fr/annonces/'.$adID.".htm";

        if ($cache_url != "false") {
            $has_cache = $checkUserCall->checkCache($request_url);
            if ($has_cache != false) {
                $response = new Response();
                $response->setContent($has_cache);
                $response->headers->set('Content-Type', 'application/json');
                ParsingTools::addRequest($this, $profile, 6, null, null);
                return $response;
            }
        }

        //echo $request_url;
        try {
            $html = file_get_html($request_url);
        } catch (Exception $e) {
            $response = new Response();
            $response->setContent(json_encode(array('error' => 'No results found')));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        if ($html->find('div[class=lbcContainer]', 0) == null) {
            $response = new Response();
            $response->setContent(json_encode(array('error' => 'No results found')));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        $price = $html->find('span[itemprop=price]', 0);
        if ($price != null) $price = $price->content;
        $date = $html->find('div[class=upload_by]', 0)->plaintext;
        $date = explode('Mise en ligne le ', $date);
        $date = explode(' &agrave; ', $date[1]);
        $date = $date[0].' '.explode('.', $date[1])[0];
        $ads = 'offers';
        if ($html->find('ul[id=nav_main]', 0)->find('li', 3)->class == 'demande selected') $ads = 'demands';
        $type = $html->find('div[class=upload_by]', 0)->plaintext;
        if (strpos($type,'Pro ') !== false) $type = 'pro';
        else $type = 'ind';
        $image = $html->find('a[id=image]', 0);
        if ($image != null) {
            $image = explode('url(\'', $image->style)[1];
            $image = explode('\');', $image)[0];
            $nb_images = $html->find('div[class=thumbs_carousel_window]', 0);
            if ($nb_images == null) {
                $nb_images = 1;
                $thumbs = array();
            }
            else {
                $images_thumbs = $html->find('div[class=thumbs_carousel_window]', 0)->find('span[class=thumbs]');
                $nb_images = count($images_thumbs);
                $thumbs = array();
                foreach($images_thumbs as $element) {
                    $thumb = explode('url(\'', $element->style)[1];
                    $thumb = explode('\');', $thumb)[0];
                    array_push($thumbs, $thumb);
                }
            }
        }
        else {
            $nb_images = 0;
            $thumbs = array();
        }

        $response = new Response();
        $response_json = json_encode(array(
            'ref' => $adID,
            'ads' => $ads,
            'type' => $type,
            'user' => utf8_encode($html->find('div[class=upload_by]', 0)->find('a', 0)->plaintext),
            'region' => utf8_encode($html->find('span[class=fine_print]', 0)->find('a', 1)->plaintext),
            'town' => utf8_encode($html->find('td[itemprop=addressLocality]', 0)->plaintext),
            'postal' => $html->find('td[itemprop=postalCode]', 0)->plaintext,
            'category' => utf8_encode($html->find('span[class=fine_print]', 0)->find('a', 2)->plaintext),
            'title' => utf8_encode($html->find('h1[id=ad_subject]', 0)->plaintext),
            'price' => $price,
            'description' => utf8_encode($html->find('div[itemprop=description]', 0)->plaintext),
            'date' => $date,
            'image' => $image,
            'nb_images' => $nb_images,
            'thumbs' => $thumbs

            //'request' => $request->query->get('name')
        ), JSON_UNESCAPED_SLASHES);
        //echo json_decode($response_json);
        $response->setContent($response_json);
        $response->headers->set('Content-Type', 'application/json');

        ParsingTools::addRequest($this, $profile, 6, null, null);

        if ($cache_url != "false") {
            /*echo '<br>--------------------------<br>';
            echo $response_json;
            echo '<br>--------------------------<br>';*/
            $checkUserCall->addChache($request_url, $response_json);
        }

        return $response;
    }

    public function mailAction(Request $request, $adID) {
        extract($_POST);

        $token_url = $request->query->get('token');
        $checkUserCall = new CheckUserCall($this->getDoctrine());
        $profile = $checkUserCall->check($token_url);
        
        if (is_string($profile)) {
            $response = new Response();
            if ($profile == 'errorTokenMissing') $response->setContent(json_encode(array('error' => 'Please provide your user token')));
            else if ($profile == 'errorBadToken') $response->setContent(json_encode(array('error' => 'Please provide a token with valid format')));
            else if ($profile == 'errorNoAccount') $response->setContent(json_encode(array('error' => 'No matching account for this token')));
            else $response->setContent(json_encode(array('error' => 'You have reach your request limit')));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        $name_post = $request->request->get('name');
        $email_post = $request->request->get('email');
        $phone_post = $request->request->get('phone');
        $body_post = $request->request->get('body');

        if ($name_post == null || $email_post == null || $body_post == null) {
            $response = new Response();
            $response->setContent(json_encode(array('error' => 'Please provide all required options to send the mail to the announcer')));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        //set POST variables
        $url = 'http://www2.leboncoin.fr/ar/send/0?id='.$adID;
        $fields = array(
                'name' => urlencode($name_post),
                'email' => urlencode($email_post),
                'phone' => urlencode($phone_post),
                'body' => urlencode($body_post),
                'cc' => urlencode('1'),
                'send' => urlencode('Envoyer')
        );

        //url-ify the data for the POST
        $fields_string = '';
        foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
        rtrim($fields_string, '&');

        //open connection
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'http://www2.leboncoin.fr/ar/form/0?ca=12_s&id='.$adID);
        curl_setopt($ch, CURLOPT_POST, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "");
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt ($ch, CURLOPT_COOKIEJAR, COOKIE_FILE);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt ($ch, CURLOPT_COOKIEFILE, COOKIE_FILE);
        curl_exec($ch);

        //set the url, number of POST vars, POST data
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_POST, count($fields));
        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);

        //execute post
        $result = curl_exec($ch);
        //echo $result;

        //close connection
        curl_close($ch);

        $response = new Response();

        //echo json_decode($response_json);
        if ($result != false && strpos($result,'Votre message a &eacute;t&eacute; envoy&eacute; &agrave; l\'annonceur !') != false) $response->setContent(json_encode(array('success' => 'Mail succuessfully sent to the announcer')));
        else $response->setContent(json_encode(array('error' => 'A problem occured while sending the mail sent to the announcer')));

        $response->headers->set('Content-Type', 'application/json');

        ParsingTools::addRequest($this, $profile, 7, null, null);

        return $response;
    }

    public function postAction(Request $request) {
        extract($_POST);

        $token_url = $request->query->get('token');
        $checkUserCall = new CheckUserCall($this->getDoctrine());
        $profile = $checkUserCall->check($token_url);
        
        if (is_string($profile)) {
            $response = new Response();
            if ($profile == 'errorTokenMissing') $response->setContent(json_encode(array('error' => 'Please provide your user token')));
            else if ($profile == 'errorBadToken') $response->setContent(json_encode(array('error' => 'Please provide a token with valid format')));
            else if ($profile == 'errorNoAccount') $response->setContent(json_encode(array('error' => 'No matching account for this token')));
            else $response->setContent(json_encode(array('error' => 'You have reach your request limit')));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        $category_post = $request->request->get('category');
        $company_ad_post = $request->request->get('company_ad');
        $type_post = $request->request->get('type');
        $region_post = $request->request->get('region');
        $dpt_code_post = $request->request->get('dpt_code');
        $zipcode_post = $request->request->get('zipcode');
        $city_post = $request->request->get('city');
        $name_post = $request->request->get('name');
        $email_post = $request->request->get('email');
        $phone_post = $request->request->get('phone');
        $phone_hidden_post = $request->request->get('phone_hidden');
        $no_salesmen_post = $request->request->get('no_salesmen');
        $subject_post = $request->request->get('subject');
        $body_post = $request->request->get('body');
        $price_post = $request->request->get('price');
        $passwd_post = $request->request->get('passwd');

        if ($passwd_post == null || $category_post == null || $company_ad_post == null || $type_post == null || $region_post == null || $dpt_code_post == null || $zipcode_post == null || $city_post == null || $name_post == null || $email_post == null || $phone_post == null || $subject_post == null || $body_post == null) {
            $response = new Response();
            $response->setContent(json_encode(array('error' => 'Please provide all required options to publish a new ad')));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        //set POST variables
        $url = 'http://www2.leboncoin.fr/ai/verify/1';
        $fields = array(
            'category' => urlencode($category_post),
            'company_ad' => urlencode($company_ad_post),
            'type' => urlencode($type_post),
            'region' => urlencode($region_post),
            'dpt_code' => urlencode($dpt_code_post),
            'zipcode' => urlencode($zipcode_post),
            'city' => urlencode($city_post),
            'name' => urlencode($name_post),
            'email' => urlencode($email_post),
            'phone' => urlencode($phone_post),
            'phone_hidden' => ($phone_hidden_post == null) ? null : urlencode($phone_hidden_post),
            'no_salesmen' => ($no_salesmen_post == null) ? null : urlencode($no_salesmen_post),
            'subject' => urlencode($subject_post),
            'body' => urlencode($body_post),
            'price' => ($price_post == null) ? null : urlencode($price_post),
            /*'category' => urlencode('38'),
            'company_ad' => urlencode('0'),
            'type' => urlencode('s'),
            'region' => urlencode('12'),
            'dpt_code' => urlencode('92'),
            'zipcode' => urlencode('92800'),
            'city' => urlencode('Puteaux'),
            'name' => urlencode('Tommy'),
            'email' => urlencode('tommy.lopes@epita.fr'),
            'phone' => urlencode('0659301096'),
            'phone_hidden' => urlencode('1'),
            'no_salesmen' => urlencode('1'),
            'subject' => urlencode('Un objet test'),
            'body' => urlencode('un super objet trop beau qui va faire fureur'),
            'price' => urlencode('10'),*/

            /*'check_type_diff' => '0',
            'address' => null,
            'accept_localisation' => 'on',
            'latitude' => null,
            'longitude' => null,
            'geo_source' => null,
            'geo_provider' => null,
            'meeting_point_id' => null,
            'siren' => null,
            'clothing_type' => '0',
            'clothing_st' => '0',
            'shoe_type' => '0',
            'shoe_size' => '0',
            'baby_age' => null,
            'jobcontract' => '0',
            'jobfield' => '0',
            'jobduty' => '0',
            'jobexp' => '0',
            'jobstudy' => '0',
            'jobtime' => '1',
            'brand' => null,
            'regdate' => null,
            'mileage' => null,
            'real_estate_type' => null,
            'square' => null,
            'rooms' => null,
            'furnished' => null,
            'energy_rate' => null,
            'ges' => null,
            'capacity' => null,
            'swimming_pool' => null,
            'bedrooms' => null,

            'datepicker_begin_date_0' => null,
            'availability_begin_date_0' => null,
            'datepicker_end_date_0' => null,
            'availability_end_date_0' => null,
            'availability_price_0' => null,

            'datepicker_begin_date_1' => null,
            'availability_begin_date_1' => null,
            'datepicker_end_date_1' => null,
            'availability_end_date_1' => null,
            'availability_price_1' => null,

            'datepicker_begin_date_2' => null,
            'availability_begin_date_2' => null,
            'datepicker_end_date_2' => null,
            'availability_end_date_2' => null,
            'availability_price_2' => null,

            'datepicker_begin_date_3' => null,
            'availability_begin_date_3' => null,
            'datepicker_end_date_3' => null,
            'availability_end_date_3' => null,
            'availability_price_3' => null,

            'datepicker_begin_date_4' => null,
            'availability_begin_date_4' => null,
            'datepicker_end_date_4' => null,
            'availability_end_date_4' => null,
            'availability_price_4' => null,

            'datepicker_begin_date_5' => null,
            'availability_begin_date_5' => null,
            'datepicker_end_date_5' => null,
            'availability_end_date_5' => null,
            'availability_price_5' => null,

            'datepicker_begin_date_6' => null,
            'availability_begin_date_6' => null,
            'datepicker_end_date_6' => null,
            'availability_end_date_6' => null,
            'availability_price_6' => null,

            'datepicker_begin_date_7' => null,
            'availability_begin_date_7' => null,
            'datepicker_end_date_7' => null,
            'availability_end_date_7' => null,
            'availability_price_7' => null,

            'datepicker_begin_date_8' => null,
            'availability_begin_date_8' => null,
            'datepicker_end_date_8' => null,
            'availability_end_date_8' => null,
            'availability_price_8' => null,

            'datepicker_begin_date_9' => null,
            'availability_begin_date_9' => null,
            'datepicker_end_date_9' => null,
            'availability_end_date_9' => null,
            'availability_price_9' => null,

            'datepicker_begin_date_10' => null,
            'availability_begin_date_10' => null,
            'datepicker_end_date_10' => null,
            'availability_end_date_10' => null,
            'availability_price_10' => null,

            'datepicker_begin_date_11' => null,
            'availability_begin_date_11' => null,
            'datepicker_end_date_11' => null,
            'availability_end_date_11' => null,
            'availability_price_11' => null,

            'datepicker_begin_date_12' => null,
            'availability_begin_date_12' => null,
            'datepicker_end_date_12' => null,
            'availability_end_date_12' => null,
            'availability_price_12' => null,

            'datepicker_begin_date_13' => null,
            'availability_begin_date_13' => null,
            'datepicker_end_date_13' => null,
            'availability_end_date_13' => null,
            'availability_price_13' => null,

            'datepicker_begin_date_14' => null,
            'availability_begin_date_14' => null,
            'datepicker_end_date_14' => null,
            'availability_end_date_14' => null,
            'availability_price_14' => null,

            'datepicker_begin_date_15' => null,
            'availability_begin_date_15' => null,
            'datepicker_end_date_15' => null,
            'availability_end_date_15' => null,
            'availability_price_15' => null,

            'datepicker_begin_date_16' => null,
            'availability_begin_date_16' => null,
            'datepicker_end_date_16' => null,
            'availability_end_date_16' => null,
            'availability_price_16' => null,

            'datepicker_begin_date_17' => null,
            'availability_begin_date_17' => null,
            'datepicker_end_date_17' => null,
            'availability_end_date_17' => null,
            'availability_price_17' => null,

            'datepicker_begin_date_18' => null,
            'availability_begin_date_18' => null,
            'datepicker_end_date_18' => null,
            'availability_end_date_18' => null,
            'availability_price_18' => null,

            'datepicker_begin_date_19' => null,
            'availability_begin_date_19' => null,
            'datepicker_end_date_19' => null,
            'availability_end_date_19' => null,
            'availability_price_19' => null,

            'datepicker_begin_date_20' => null,
            'availability_begin_date_20' => null,
            'datepicker_end_date_20' => null,
            'availability_end_date_20' => null,
            'availability_price_20' => null,

            'datepicker_begin_date_21' => null,
            'availability_begin_date_21' => null,
            'datepicker_end_date_21' => null,
            'availability_end_date_21' => null,
            'availability_price_21' => null,

            'datepicker_begin_date_22' => null,
            'availability_begin_date_22' => null,
            'datepicker_end_date_22' => null,
            'availability_end_date_22' => null,
            'availability_price_22' => null,

            'datepicker_begin_date_23' => null,
            'availability_begin_date_23' => null,
            'datepicker_end_date_23' => null,
            'availability_end_date_23' => null,
            'availability_price_23' => null,

            'price_min' => null,
            'price_max' => null,
            'cubic_capacity' => null,
            'fuel' => null,
            'gearbox' => '0',
            'animal_type' => '0',
            'animal_race' => '0',
            'animal_litter' => null,
            'animal_age' => '0',
            'animal_identification' => null,
            'custom_ref' => null,
            'charges_included' => null,
            'fai_included' => null,

            'image0' => null,
            'image1' => null,
            'image2' => null,
            'image3' => null,
            'image4' => null,
            'image5' => null,
            'image6' => null,
            'image7' => null,
            'image8' => null,
            'image9' => null,

            'validate' => 'Valider'*/
        );


        //url-ify the data for the POST
        $fields_string = '';
        foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
        rtrim($fields_string, '&');

        //open connection
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'http://www2.leboncoin.fr/ai/form/1?ca=12_k');
        curl_setopt($ch, CURLOPT_POST, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "");
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt ($ch, CURLOPT_COOKIEJAR, COOKIE_FILE);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt ($ch, CURLOPT_COOKIEFILE, COOKIE_FILE);
        curl_exec($ch);

        //set the url, number of POST vars, POST data
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_POST, count($fields));
        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);

        //execute post
        $result = curl_exec($ch);
        //echo $result;

        $url = 'http://www2.leboncoin.fr/ai/create/1';
        $fields = array(
            'ca' => urlencode('12_s'),
            'category' => urlencode($category_post),
            'city' => urlencode($city_post),
            'passwd' => urlencode($passwd_post),
            'passwd_ver' => urlencode($passwd_post),
            'create' => urlencode('Valider'),
            'create2' => urlencode('Valider'),
        );

        //url-ify the data for the POST
        $fields_string = '';
        foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
        rtrim($fields_string, '&');

        //set the url, number of POST vars, POST data
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_POST, count($fields));
        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);

        //execute post
        $result = curl_exec($ch);
        //echo $result;

        //close connection
        curl_close($ch);

        $response = new Response();

        //echo json_decode($response_json);
        if ($result != false && strpos($result,'Un email de confirmation vient de vous &ecirc;tre envoy&eacute;') != false) $response->setContent(json_encode(array('success' => 'Ad succuessfully published on the website')));
        else $response->setContent(json_encode(array('error' => 'A problem occured while publishing the ad on the website')));

        $response->headers->set('Content-Type', 'application/json');

        ParsingTools::addRequest($this, $profile, 8, null, null);

        return $response;
    }
}
