<?php

namespace sessnex\persistent;

interface persistentInterface
{

	public function storeToken($token);
	
	public function getExistingData($storedToken);
	
	public function cookieLogin($storedToken);

	public function checkcreadentials($cookie);

	public function trashLoginCreadentials();
	
	public function persistentLogin();
}