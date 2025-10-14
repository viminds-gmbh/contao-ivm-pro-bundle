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
use Qbus\IvmProClient\ApiClient;
use Qbus\IvmProClient\ApiRequest\Data as DataRequest;
use Qbus\IvmProClient\ApiRequest\Districts as DistrictsRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class IvmProFilterController extends AbstractFrontendModuleController
{
    private $apiClient;
    private $translator;

    public function __construct(ApiClient $apiClient, TranslatorInterface $translator)
    {
        $this->apiClient = $apiClient;
        $this->translator = $translator;
    }

    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        global $objPage;

        $inputFilters = [];

        foreach (['flat_district', 'flat_rooms_from', 'flat_rooms_to', 'flat_floors'] as $inputFilter) {
            if ($inputFilterVal = Input::get($inputFilter)) {
                $inputFilters[$inputFilter]
                    = \in_array($inputFilter, ['flat_district', 'flat_floors'], true)
                        ? [$inputFilterVal]
                        : $inputFilterVal
                ;
            }
        }

        $districtsResponse = $this->apiClient->sendRequest((new DistrictsRequest()));
        $districtFilter = [];

        if (200 === $districtsResponse->getStatusCode()) {
            $districtsRaw = $districtsResponse->getBody()->getContents();
            $districts = \unserialize(\json_decode($districtsRaw), ['allowed_classes' => false]);

            if ($districts) {
                $districtFilter = $this->getFilterList($districts, $inputFilters, ['flat_district'], 'IVMPRO.filter.allDistricts', true);
            }
        }
        $template->districts = $districtFilter;

        $dataResponse = $this->apiClient->sendRequest((new DataRequest()));
        $roomFilter = $floorFilter = [];

        if (200 === $dataResponse->getStatusCode()) {
            $dataRaw = $dataResponse->getBody()->getContents();
            $data = \unserialize(\json_decode($dataRaw), ['allowed_classes' => false]);

            if ($data) {
                if ($data['rooms']) {
                    $roomFilter = $this->getFilterList($data['rooms'], $inputFilters, ['flat_rooms_from', 'flat_rooms_to'], 'IVMPRO.filter.allRooms');
                }

                if ($data['floors']) {
                    $floorFilter = $this->getFilterList($data['floors'], $inputFilters, ['flat_floors'], 'IVMPRO.filter.allFloors');
                }
            }
        }
        $template->rooms = $roomFilter;
        $template->floors = $floorFilter;

        return $template->getResponse();
    }

    protected function getFilterList(
        array $filterData,
        array $inputFilters,
        array $thisFilterKeys,
        string $allTransKey,
        bool $useDataKeyAsUrlValue = false
    ): array
    {
        global $objPage;

        $filterList = [];

        $otherFilters = $inputFilters;
        foreach ($thisFilterKeys as $thisFilterKey) {
            unset($otherFilters[$thisFilterKey]);
        }

        $otherUrlFragment = '';
        foreach ($otherFilters as $otherKey => $otherVal) {
            $otherUrlFragment .= '/'.$otherKey.'/'.(\is_array($otherVal) ? $otherVal[0] : $otherVal);
        }

        $allUrl = $objPage->getFrontendUrl($otherUrlFragment);
        $filterList[$allUrl] = [
            'label' => $this->translator->trans($allTransKey, [], 'contao_default'),
            'active' => !isset($inputFilters[$thisFilterKeys[0]]),
        ];

        foreach ($filterData as $key => $value) {
            $urlValue = $useDataKeyAsUrlValue ? $key : $value;
            $filterUrlFragment = $otherUrlFragment;
            foreach ($thisFilterKeys as $filterKey) {
                $filterUrlFragment .= '/' . $filterKey . '/' . $urlValue;
            }
            $filterUrl = $objPage->getFrontendUrl($filterUrlFragment);
            // TODO: Move this into a callback to fully generalize this method
            switch ($thisFilterKeys[0]) {
                case 'flat_district':
                    $label = $value['name'];
                    break;
                case 'flat_rooms_from':
                    $roomFullHalf = \explode('.', $value);
                    $label = $roomFullHalf[0].($roomFullHalf[1] ? (' '.$roomFullHalf[1].'/2') : '');
                    break;
                default:
                    $label = $value;
            }
            $active = false;
            if (isset($inputFilters[$thisFilterKeys[0]])) {
                $checkActive = \is_array($inputFilters[$thisFilterKeys[0]]) ? $inputFilters[$thisFilterKeys[0]][0] : $inputFilters[$thisFilterKeys[0]];
                $active = $checkActive == $urlValue;
            }
            $filterList[$filterUrl] = [
                'label' => $label,
                'active' => $active,
            ];
        }

        return $filterList;
    }
}
