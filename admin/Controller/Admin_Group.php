<?php

namespace Admin\Controller;

use Core\View\Paging;
use Core\Orm;
use Core\Router;

class Admin_group extends Controller
{
	public function methodIndex($args)
	{
		$data = [];

		$paging = Paging::create('Admin_Group', [
			'page_size' => 10,
			'current_page' => empty($args['page']) ? 1 : (int)$args['page']
		]);

		$data['paging'] = $paging->getPaging();
		$data['groups'] = $paging->getObjects();

		$data['content'] = $this->view->render('templates/modules/admin_group/index.phtml', $data);

		return $this->render($data);
	}

	public function methodNew()
	{
		$data['content'] = $this->view->render('templates/modules/admin_group/add.phtml', []);
		return $this->render($data);
	}

	public function methodEdit($args)
	{
		$data['group'] = Orm::load('Admin_Group', $args['edit'])->getValues();
		$data['content'] = $this->view->render('templates/modules/admin_group/edit.phtml', $data);

		return $this->render($data);
	}

	public function methodSave($args)
	{
		if (!empty($args['id'])) {
			$group = Orm::load('Admin_Group', $args['id']);
		} else {
			$group = Orm::create('Admin_Group');
		}

		$group->setValues($args);

		try {
			Orm::save($group);
		} catch (\Core\Exception\UserInterface\ObjectValidationException $e) {
			$this->view->addNotice('error', $e->getMessage());
			if ($group->isNew()) {
				Router::redirect('/admin/admin_group/new');
			}
		}

		Router::redirect('/admin/admin_group/edit/' . $group->getId());
	}

	public function methodDuplicate($args)
	{
		$group = Orm::load('Admin_Group', $args['duplicate']);
		$data = $group->getValues();
		unset($data['id']);

		$newPage = Orm::create('Admin_Group');
		$newPage->setValues($data);
		Orm::save($newPage);

		Router::redirect('/admin/admin_group/');
	}

	public function methodDelete($args)
	{
		$group = Orm::load('Admin_Group', $args['delete']);

		Orm::delete($group);
		Router::redirect('/admin/admin_group/');
	}
}
