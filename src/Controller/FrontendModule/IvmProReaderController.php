<?php

declare(strict_types=1);

/*
 * This file is part of IVM Professional Bundle for Contao.
 *
 * (c) Qbus Internetagentur
 *
 * @license LGPL-3.0-or-later
 */

namespace Qbus\IvmProBundle\Controller\FrontendModule;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\Input;
use Contao\ModuleModel;
use Contao\Template;
use Qbus\IvmProClient\Repository\UnitRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class IvmProReaderController extends AbstractFrontendModuleController
{
    private $unitRepository;
    private $subdomain;

    public function __construct(UnitRepository $unitRepository, string $subdomain)
    {
        $this->unitRepository = $unitRepository;
        $this->subdomain = $subdomain;
    }

    protected function getResponse(Template $template, ModuleModel $model, Request $request): ?Response
    {
        $id = Input::get('auto_item');

        if (!$id) {
            Input::setUnusedGet('auto_item', $id);

            return $template->getResponse();
        }

        $unitModel = $this->unitRepository->findById((int) $id);

        if (null === $unitModel) {
            Input::setUnusedGet('auto_item', $id);

            return $template->getResponse();
        }

        foreach (\get_class_methods($unitModel) as $method) {
            if (0 !== \strpos($method, 'get')) {
                continue;
            }

            $property = \lcfirst(\substr($method, 3));
            $unit[$property] = $unitModel->$method();
        }

        $ivmUrl = \sprintf('https://%s.ivm-professional.de', $this->subdomain);

        if ($unit['image']) {
            $unit['image'] = $ivmUrl.'/_img/flats/'.$unit['image'];
        }

        if ($unit['gallery_img']) {
            $images = \unserialize(\urldecode($unit['gallery_img']), ['allowed_classes' => false]);

            if ($images) {
                $unit['gallery_img'] = array_map(
                    static function ($imgData) use ($ivmUrl, $id) {
                        $imgData['url'] = $ivmUrl.'/_img/gallery/'.$id.'/img_'.$imgData['name'];

                        return $imgData;
                    },
                    $images
                );
            }
            // TODO: If there are no gallery images, then, instead of an empty
            //       string, the API provides some url-encoded gunk that should
            //       better already be filtered out in the client.
            else {
                $unit['gallery_img'] = null;
            }
        }

        if ($unit['plot']) {
            $unit['plot'] = $ivmUrl.'/_img/plots/'.$unit['plot'];
        }

        if ($unit['environmet']) {
            $unit['environmet'] = \unserialize(\urldecode($unit['environmet']), ['allowed_classes' => false]);
        }

        $template->unit = $unit;

        return $template->getResponse();
    }
}
