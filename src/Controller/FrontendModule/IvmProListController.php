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
use Contao\PageModel;
use Contao\Template;
use Qbus\IvmProClient\Repository\UnitRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class IvmProListController extends AbstractFrontendModuleController
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
        $readerPage = PageModel::findByPk($model->jumpTo);

        if (null !== $readerPage) {
            $template->readerPage = $readerPage;
        }

        $filters = [];

        foreach (['flat_district', 'flat_rooms_from', 'flat_rooms_to', 'flat_floors'] as $filter) {
            if ($filterVal = Input::get($filter)) {
                $filters[$filter] = \in_array($filter, ['flat_district', 'flat_floors'], true) ? [$filterVal] : $filterVal;
            }
        }

        if (empty($filters)) {
            $unitModels = $this->unitRepository->findAll();
        } else {
            $unitModels = $this->unitRepository->findBySearchCriteria($filters);
        }

        if (null === $unitModels) {
            return $template->getResponse();
        }

        $units = [];

        foreach ($unitModels as $key => $unitModel) {
            foreach (\get_class_methods($unitModel) as $method) {
                if (0 !== \strpos($method, 'get')) {
                    continue;
                }

                $property = \lcfirst(\substr($method, 3));
                $units[$key][$property] = $unitModel->$method();
            }

            if ($units[$key]['image']) {
                $ivmUrl = \sprintf('https://%s.ivm-professional.de', $this->subdomain);
                $units[$key]['image'] = $ivmUrl.'/_img/flats/'.$units[$key]['image'];
            }

            $units[$key]['readerHref'] = $readerPage !== null ? $readerPage->getFrontendUrl('/'.$units[$key]['id']) : '';
        }
        $template->units = $units;

        return $template->getResponse();
    }
}
