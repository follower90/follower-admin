<?php

namespace App\EntryPoint;

use Core\App;

class Api extends \Core\EntryPoint
{
	public function init()
	{
		\Admin\Utils::setLanguage();
		\Admin\Utils::setCurrency();
		$this->setLib('\App\Api');

		$app = new App($this);

		$authorize = new \Admin\Authorize('User');
		$authorize->getUser();

		$app->run();
	}

	public function debug()
	{
		if ($this->request('debug') == 'on') {
			return true;
		}

		return false;
	}

	public function output($data)
	{
		header('Content-Type: application/json');

		if ($this->debug()) {
			header('Content-Type: text/html');
		}

		return json_encode($data);
	}
}
