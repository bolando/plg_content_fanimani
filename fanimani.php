<?php

defined( '_JEXEC' ) or die;

jimport('joomla.log.log');

class plgContentFanimani extends JPlugin
{
	protected $autoloadLanguage = true;
	
	
	public function __construct(& $subject, $config){
		parent::__construct($subject, $config);
		$this->loadLanguage();
	}
	
	//Called after loading the form description
	//display menu in upper-menu (visible like a tab)
	public function onContentPrepareForm($form, $data)//OK
    {
      if (!($form instanceof JForm))
      {
         $this->_subject->setError('JERROR_NOT_A_FORM');
         return false;
      }
	  
	  // only for catid=10 ['nasi beneficjenci']
	  if( isset($data->{'catid'}) && $data->{'catid'} != 10 )
		  return true;
	  
      // Add the extra fields to the form.
      JForm::addFormPath(dirname(__FILE__) . '/fanimani');
      $form->loadFile('fanimani', false);
      return true;
    }
   
    public function onContentAfterDelete($context, $article){
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->delete($db->quoteName('#__user_profiles'))
			->where('user_id = '.$article->{id})
			->where('profile_key LIKE ' . $db->Quote('fanimani.%'));
		$db->setQuery($query);
		$db->execute();
		//JError::raiseNotice( 100+strlen(print_r($res,true)),"onContentAfterDelete! [".print_r($res,true)."]<br/>" );
    }
	
	public function onContentBeforeSave($context, $article, $isNew) {
		//$a = print_r($article, true);
		//JError::raiseNotice( 100+strlen($a),"<br/><br/>Results: ".$a."<br/><br/>" );
			if( !isset($article->{attribs}) )
				return true;
			if( isset(json_decode($article->{attribs})->{fanimani_exists}) && json_decode($article->{attribs})->{fanimani_exists} == 1 ){
				//JError::raiseNotice( 100,"Dodawanie!<br/>" );
				$db = JFactory::getDbo();
				// ustawianie danych 
				$dane = array();
					$dane["name"] = $article->{title};
					//JError::raiseNotice( 100,$article->{title}."<br/>" );
					$dane["slug"] = str_replace('_','-',strtolower($article->{alias}));
					//JError::raiseNotice( 1000,strtolower($article->{alias})."<br/>" );
					$dane["description"] = ($this->rip_tags($article->{fulltext}));
					//JError::raiseNotice( 10000,$article->{fulltext}."<br/>" );
				//$a = print_r($dane, true);
				//JError::raiseNotice( 100+strlen($a),"3Desc: ".$a."<br/>" );
				
				/****** PHOTO UPDATE *****/
					$dane["images"] = json_decode($article->{images});
				
				//old user
				if( isset(json_decode($article->{attribs})->{fanimani_id}) && json_decode($article->{attribs})->{fanimani_id}!='' ){
					$dane['id'] = json_decode($article->{attribs})->{fanimani_id};
				}
				
				
				// tworzenie i wysyłanie danych 
				$user = $this->createUser($dane);
		//$a = print_r($user, true);
		//JError::raiseNotice( 100+strlen($a),"<br/><br/>Results: ".$a."<br/><br/>" );
				$response = $this->addUser($user);
		//$a = print_r($response, true);
		//JError::raiseNotice( 100+strlen($a),"<br/><br/>a_Results: ".$a."<br/><br/>" );
				if( $response == null ){
					JError::raiseNotice( 100,"Problem przy wysyłaniu do serwisu avalon.<br/>" );
					return true;
				}
				
				$response = json_decode($response);
	
	
				if( isset($response->{id}) ){
					$query = $db->getQuery(true)
						->delete($db->quoteName('#__user_profiles'))
						->where('user_id = '.$article->{id})
						->where('profile_key IN (' . $db->Quote('fanimani.fanimani_url').', '.$db->Quote('fanimani.fanimani_msg').', '. $db->Quote('fanimani.fanimani_id').', '. $db->Quote('fanimani.fanimani_exists') .')');
					$db->setQuery($query);
					$db->execute();
					$query = $db->getQuery(true)
						->insert($db->quoteName('#__user_profiles'))
						->columns($db->quoteName(array('user_id','profile_key','profile_value')))
						->values(implode(',',array($article->{id},$db->Quote("fanimani.fanimani_url"),$db->Quote("https://fanimani.pl".$response->url))))
						->values(implode(',',array($article->{id},$db->Quote("fanimani.fanimani_id"),$db->Quote($response->{id}))))
						->values(implode(',',array($article->{id},$db->Quote("fanimani.fanimani_msg"),$db->Quote(JText::_('PLG_CONTENT_FANIMANI_IS_IN_DB')))))
						->values(implode(',',array($article->{id},$db->Quote("fanimani.fanimani_exists"),$db->Quote('1'))));
					$db->setQuery($query);
					$res = $db->execute();
					
					
					 $query = $db->getQuery(true)
						->select("profile_key, profile_value")
						->from($db->quoteName('#__user_profiles'))
						->where('user_id = '.$article->{id})
						->where('profile_key LIKE ' . $db->Quote('fanimani.%'));
					$db->setQuery($query);
					$results = $db->loadRowList();
							
					$df = array();
					foreach($results as $k=>$v){
						$df[str_replace('fanimani.','',$v[0])] = $v[1];
					}
					$article->{fanimani} = $df;
					
		//$a = print_r($results, true);
		//JError::raiseNotice( 100+strlen($a),"<br/><br/>Results: ".$a."<br/><br/>" );
		//JError::raiseNotice( 100,"Zapisano!<br/>" );
			/***************  SEND A PHOTOS ******************/
					//$user->{'photos'}[0]->{'photo'} // path
					//$response->{'id'} //user id
					
					$query = $db->getQuery(true)
						->delete($db->quoteName('#__user_profiles'))
						->where('user_id = '.$article->{id})
						->where('profile_key = ' . $db->Quote('fanimani.fanimani_images'));
					$db->setQuery($query);
					$db->execute();
					
					
					$photos = (isset($article->{fanimani}->{fanimani_images}))?$article->{fanimani}->{fanimani_images}:"[]";
					$photos = json_decode($photos);//[24:link,35:link]
					$newPhoto = array();
					foreach($user->{'photos'} as $img){
						if( !in_array($img->{'photo'}, $photos) ){
							if( !in_array($img->{'photo'},$newPhoto) )
								$newPhoto[] = $img->{'photo'};//http://www.fundacjaavalon.pl/
						}
					}
					/**************** SENDING A PICTURES **************/	
					foreach($newPhoto as $img){
						$path = "../".$img;
						$results = $this->curlQueryPictures($response->{'id'}, $path);
						if( $results != null ){
							$results = json_decode($results);
							$photos[$results->{id}] = $img;
						}
					}
					//$a = print_r($photos, true);
					//JError::raiseNotice( 100+strlen($a),"<br/><br/>Results: ".$a."<br/><br/>" );
					$photos = json_encode($photos);
					$query = $db->getQuery(true)
						->insert($db->quoteName('#__user_profiles'))
						->columns($db->quoteName(array('user_id','profile_key','profile_value')))
						->values(implode(',',array($article->{id},$db->Quote("fanimani.fanimani_images"),$db->Quote($photos))));
					$db->setQuery($query);
					$results = $db->loadRowList();//this will execute the query
					$article->{fanimani}->{fanimani_images} = $photos;
					
					
					/*$a = print_r($newPhoto, true);
					JError::raiseNotice( 100+strlen($a),"<br/><br/>Results: ".$a."<br/><br/>" );
					$photos = (isset($article->{fanimani}['fanimani_images']))?$article->{fanimani}['fanimani_images']:"[]";
					$photos = json_decode($photos);//[24:link,35:link]
					if( $photos == null ) $photos = array();
					$newPhoto = array();
					foreach($user->{'photos'} as $img){
						if( !in_array($img->{'photo'}, $photos) ){
							if( !in_array($img->{'photo'},$newPhoto) )
								$newPhoto[] = $img->{'photo'};//http://www.fundacjaavalon.pl/
						}
					}*/
					//$a = print_r($newPhoto, true);
					//JError::raiseNotice( 100+strlen($a),"<br/><br/>Results: ".$a."<br/><br/>" );
					
					/**************** SENDING A PICTURES **************/					
					/*foreach($newPhoto as $img){
						$path = "../".$img;
						//$b64_img = 'data:image/' . pathinfo($path, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($path));
						
						//$results = null;
						$results = $this->curlQueryPictures($response->{'id'}, $path);
						
						if( $results != null ){
							$results = json_decode($results);
							$photos[$results->{id}] = $img;
						}
						/*$args['photo'] = null;
						if( class_exists('CurlFile') ){
							$args['photo'] = new CurlFile($path, pathinfo($path, PATHINFO_EXTENSION));
						} else if( function_exists('curl_file_create')){
							$args['photo'] = curl_file_create($path, pathinfo($path, PATHINFO_EXTENSION));
						} else {
							$args['photo'] = "@{$path};filename="
											. ($postname ?: basename($filename))
											. ";type=".pathinfo($path, PATHINFO_EXTENSION);
						}
						
							$a = print_r($args, true);
							JError::raiseNotice( 100+strlen($a),"<br/><br/>Response: ".$a."<br/><br/>" );
						
						
						$a = print_r(array("id"=>$response->{'id'}, "path"=>"../".$img), true);
						JError::raiseNotice( 100+strlen($a),"<br/><br/>Response: ".$a."<br/><br/>" );
						*//*
						$a = print_r($results, true);
						JError::raiseNotice( 100+strlen($a),"<br/><br/>Response: ".$a."<br/><br/>" );
					}
					
						$a = print_r($photos, true);
						JError::raiseNotice( 100+strlen($a),"<br/><br/>1Response: ".$a."<br/><br/>" );
					
					$photos = json_encode($photos);
						$a = print_r($photos, true);
						JError::raiseNotice( 100+strlen($a),"<br/><br/>1Response: ".$a."<br/><br/>" );
					$query = $db->getQuery(true)
						->insert($db->quoteName('#__user_profiles'))
						->columns($db->quoteName(array('user_id','profile_key','profile_value')))
						->values(implode(',',array($article->{id},$db->Quote("fanimani.fanimani_images"),$db->Quote($photos))));
					$db->setQuery($query);
					$results = $db->loadRowList();
						$a = print_r($results, true);
						JError::raiseNotice( 100+strlen($a),"<br/><br/>2Response: ".$a."<br/><br/>" );
							
					$df = array();
					foreach($results as $k=>$v){
						$df[str_replace('fanimani.','',$v[0])] = $v[1];
					}
						$a = print_r($df, true);
						JError::raiseNotice( 100+strlen($a),"<br/><br/>3Response: ".$a."<br/><br/>" );
					$article->{fanimani} = $df;*/
					
				}
			} else {
				//JError::raiseNotice( 100,"Usuwanie!<br/>" );
				$attribs = json_decode($article->{attribs});
				//JError::raiseNotice( 100, $attribs->{fanimani_id}."<br/>" );
				if( !isset($attribs->{fanimani_id}) )
					return true;
				if( $attribs->{fanimani_id}=='' )
					return true;
				$this->curlQuery(null,$attribs->{fanimani_id},"DELETE");
				$this->onContentAfterDelete($context,$article);
			}
		
		return true;
	}
   
   //Called after loading the article from the database
   //load script which show content from tab-menu in content-view _&&_ load data about settings this content
   public function onContentPrepareData($context, $data)
   {
	  $cols = array("fanimani_id"=>"","fanimani_url"=>"","fanimani_msg"=>"","fanimani_exists"=>0);
      if (is_object($data))
      {
		foreach($cols as $k=>$v)
			$data->{attribs}[$k] = $v;
		  
		 $db = JFactory::getDbo();
         $articleId = isset($data->id) ? $data->id : 0;
		 $popup = array();
		 if( $articleId != 0 ){
			 $query = $db->getQuery(true)
				->select("profile_key, profile_value")
				->from($db->quoteName('#__user_profiles'))
				->where('user_id = '.$articleId)
				->where('profile_key LIKE ' . $db->Quote('fanimani.%'));
			$db->setQuery($query);
			$results = $db->loadRowList();
			foreach($results as $v)
				$popup[str_replace('fanimani.','',$v[0])] = $v[1];
			//$data->fanimani = $popup;
			foreach($popup as $k=>$v)
				$data->{attribs}[$k] = $v;
			
			
			 /*$query = $db->getQuery(true)
				->select("user_id, profile_key, profile_value")
				->from($db->quoteName('#__user_profiles'))
				->where('profile_key LIKE ' . $db->Quote('fanimani.%'));
			$db->setQuery($query);
			$results = $db->loadRowList();
		//$a = print_r($results, true);
		//JError::raiseNotice( 100+strlen($a),"<br/><br/>Results: ".$a."<br/><br/>" );
		echo '--------------<pre>';
		echo $articleId.'<br/>';
		var_dump($results);
		echo '</pre>--------------';
		echo '--------------<pre>';
		var_dump($data);
		echo '</pre>--------------';*/
		 }
		 
		//$a = print_r($data->attribs, true);
		//JError::raiseNotice( 100+strlen($a),"3Desc: ".$a."<br/>" );
		 
		 if( !isset($data->attribs['fanimani_id']) || $data->attribs['fanimani_id'] == '' || $data->attribs['fanimani_id'] == 0 ){
			$data->attribs['fanimani_msg'] = JText::_('PLG_CONTENT_FANIMANI_NOT_IN_DB');//'Użytkownika nie ma w bazie!';
			$query = $db->getQuery(true)
				->delete($db->quoteName('#__user_profiles'))
				->where('user_id = '.$articleId)
				->where('profile_key LIKE ' . $db->Quote('fanimani.%'));
			$db->setQuery($query);
			$db->execute();
			$query = $db->getQuery(true)
				->insert($db->quoteName('#__user_profiles'))
				->columns($db->quoteName(array('user_id','profile_key','profile_value')))
				->values(implode(',',array($articleId,$db->quote("fanimani.fanimani_msg"),$db->quote(JText::_('PLG_CONTENT_FANIMANI_NOT_IN_DB')))));
			$db->setQuery($query);
			$db->execute();
		 }
		
		 echo "<script type=\"text/javascript\">";
			include (dirname(__FILE__) . '/custom_fields.js');
		 echo "</script>";
		 
      }
 
      return true;
   }

   /*
		method - null (for POST/GET) | "<whatever>" for custom method (DELETE etc.)
		pupil - object with fields for query (look at API)
		id_pupil - if have to be at the end of URL (look at API)
		******** PICTURES ******
		id_pupil -> id_photo
		pupil -> photo_query
		photos - this is query for pupil or photo
		
		e.g.:
		delete from db 	-> method="DELETE", id_pupil=<id>					{"detail":"Nie masz uprawnień, by wykonać tę czynność."}
		insert to db	-> pupil=<object>									autoryzacja - ok
		patch in db		-> method="PATCH", id_pupil=<id>, pupil=<object>	{"detail":"Nie masz uprawnień, by wykonać tę czynność."}
		list from db	-> none												brak
		
		{"slug":["Dozwolone są tylko litery łacińskie, cyfry i myślniki."]}
   */
   private function curlQuery($pupil = null, $id_pupil = null, $method = null){
		$auth = "Token 118dd1d1866da9d22210c45337c4b0b14af11a81";
		$avalon_id = "608";
		$url = "https://fanimani.pl/api/v2/organizations/{$avalon_id}/pupils/".(($id_pupil==null)?'':$id_pupil."/");
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL => $url,
			CURLOPT_HTTPHEADER => array("Authorization: {$auth}"),
			CURLOPT_USERAGENT => 'Sample cURL Request'
		));
		if( $pupil != null )
			curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($pupil));
		if( $method != null )
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method );
		$resp = curl_exec($curl);
		if( curl_errno($curl) )
			$resp = null;
		curl_close($curl);
		return $resp;
   }
   
   /*private function curlQueryPictures($id_pupil, $image = null, $id_photo = null, $method = null){
		$auth = "Token 118dd1d1866da9d22210c45337c4b0b14af11a81";
		$url = "https://fanimani.pl/api/v2/beneficiaries/{$id_pupil}/photos/".(($id_photo!=null)?$id_photo."/":'');
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL => $url,
			CURLOPT_POST => 1,
			CURLOPT_HTTPHEADER => array("Authorization: {$auth}", "Content-Type:multipart/form-data"),
			CURLOPT_USERAGENT => 'Sample cURL Request'
		));
		if( $image != null ){
			//$args['photo'] = new CurlFile($image, pathinfo($image, PATHINFO_EXTENSION));
			$args['photo'] = null;
			if( class_exists('CurlFile') ){
				$args['photo'] = new CurlFile($image, pathinfo($image, PATHINFO_EXTENSION));
			} else {
				$args['photo'] = "@{$image};filename="
								. ";type=".pathinfo($image, PATHINFO_EXTENSION);
			}
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $args);
		}
		if( $method != null )
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method );
		$resp = curl_exec($curl);
		if( curl_errno($curl) )
			$resp = null;
		curl_close($curl);
		return $resp;
   }*/
   
   
   private function curlQueryPictures($id_pupil, $image = null, $id_photo = null, $method = null){
		$auth = "Token 118dd1d1866da9d22210c45337c4b0b14af11a81";
		$url = "https://fanimani.pl/api/v2/beneficiaries/{$id_pupil}/photos/".(($id_photo!=null)?$id_photo."/":'');
	   
	    $args = array();
		
		if( $image != null ){
			$args['photo'] = new CurlFile($image, pathinfo($image, PATHINFO_EXTENSION));
		}
		
		
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_POST => 1,
			CURLOPT_POSTFIELDS => $args,
			CURLOPT_URL => $url,
			CURLOPT_HTTPHEADER => array('Authorization: Token 118dd1d1866da9d22210c45337c4b0b14af11a81', "Content-Type:multipart/form-data"),
			CURLOPT_USERAGENT => 'Codular Sample cURL Request'
		));
		if( $method != null )
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method );
		$resp = curl_exec($curl);
		if( curl_errno($curl) )
				$resp = null;
		curl_close($curl);
	   
	   
	   return $resp;
   }
   
   private function getMe($pupils, $id){
	  foreach($pupils as $k=>$value)
		  if( $value->{id} == $id )
			  return $value;
	  return null;
   }
   
   private function addUser($user){
	   $me = $this->getMe(json_decode($this->curlQuery()),$user->{id});
		//$a = print_r($me, true);
		//JError::raiseNotice( 100+strlen($a),"<br/><br/>Results: addUser-getMe(): ".$a."<br/><br/>" );
	   if( $me == null )
			return $this->curlQuery($user);
	   else
			return $this->curlQuery($user, $me->id, "PATCH");
   }
   
   private function createUser($data){
	   $obiekt = (Object)array(
					"id"=> '-1',
					"type"=> "pupil",/*
					"name"=> "",
					"slug"=> "",
					"description"=> "",*/
					"photos"=> array(),
					"visible"=> true,
					"verified"=> true
					);
		$i = 100;
		foreach($data as $k=>$v){
			if( $k == "images" ){
				foreach($v as $img){
					if( !empty($img) && $img != "" ){
						$image = new stdClass();
						$image->{'id'} = $i;
							$i = $i+1;
						$image->{'label'} = "";
						$image->{'photo'} = $img;
						$obiekt->{'photos'}[] = $image;
					}
				}
			} else
				$obiekt->{$k} = $v;
		}
		return $obiekt;
   }
   
   
   private function rip_tags($string) {
	    // ----- HTML ENTIETIES -----
		$string = html_entity_decode($string);
	   
		// ----- remove HTML TAGs -----
		$string = preg_replace ('/<[^>]*>/', ' ', $string);
	   
		// ----- remove control characters -----
		$string = str_replace("\r", '', $string);    		// --- replace with empty space
		$string = str_replace("\n", ' ', $string);   		// --- replace with space
		$string = str_replace("\t", ' ', $string);   		// --- replace with space
	   
		// ----- remove multiple spaces -----
		$string = trim(preg_replace('/ {2,}/', ' ', $string));
	   
		return $string;
	}
}


?>