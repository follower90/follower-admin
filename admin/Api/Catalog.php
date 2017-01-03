<?php

namespace Admin\Api;

use Admin\Object\Object_Resource;
use Core\Library\File;
use Core\App;
use Core\Orm;

class Catalog extends \Core\Api
{
	public function methodActive($args)
	{
		if (!$args['id']) return false;
		$admin = \Admin\Object\Catalog::find($args['id']);
		$admin->setValue('active', (int)$args['active']);
		$admin->save();

		return ['success' => true];
	}

	public function methodUploadPhoto($args)
	{
		$data = explode(',', $args['file']);
		$data = base64_decode($data[1]);

		$tmpOriginal = App::get()->getAppPath() . '/tmp/' . $args['original'];
		File::put('/storage/catalog/' . $args['id'] . '/' . $args['original'], file_get_contents($tmpOriginal));

		Object_Resource::removeResources($args['id'], 'catalog', Object_Resource::TYPE_PHOTO_ORIG);
		Object_Resource::saveResource($args['id'], 'catalog', Object_Resource::TYPE_PHOTO_ORIG, $args['original']);

		File::put('/storage/catalog/' . $args['id'] . '/' . 'photo.jpg', $data);
		Object_Resource::saveResource($args['id'], 'catalog', Object_Resource::TYPE_PHOTO, 'photo.jpg');
		return ['success' => true];
	}
}