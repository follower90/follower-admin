<?php

namespace Admin\Api;

use Core\Orm;

class Menu extends \Core\Api
{
	public function methodActive($args)
	{
		if (!$args['id']) return false;
		$page = Orm::load('Menu', $args['id']);
		$page->setValue('active', (int)$args['active']);
		Orm::save($page);

		return ['success' => true];
	}

	public function methodSort($args)
	{
		$order = $args['ids'];
		$max = count($args['ids']);

		foreach ($order as $id) {
			$menuItem = Orm::load('Menu', $id);
			$menuItem->setValue('sort', --$max);
			$menuItem->save();
		}

		return ['success' => true];
	}

}
