<?php

const TRAVEL_URL = 'https://5f27781bf5d27e001612e057.mockapi.io/webprovise/travels';
const COMPANY_URL = 'https://5f27781bf5d27e001612e057.mockapi.io/webprovise/companies';

class Travel
{
    // Enter your code here


    /**
     * get raw data from REST Api.
     *
     * @return array
     */
    public function getDataFromApi()
    {
        $travel = curl_init(TRAVEL_URL);
        curl_setopt($travel, CURLOPT_HTTPGET, true);
        curl_setopt($travel, CURLOPT_RETURNTRANSFER, true);
        $response_json = curl_exec($travel);
        curl_close($travel);
        $response = json_decode($response_json, true);

        return $response;
    }

    /**
     * get raw data from REST Api.
     *
     * @return array
     */
    public function handleData()
    {
        $rawDataItems = $this->getDataFromApi();
        $childs = [];
        foreach ($rawDataItems as &$item) {
            if (!isset($childs[$item['companyId'] ?? 0]['cost'])) {
                $childs[$item['companyId'] ?? 0]['cost'] = 0;
            }
            $childs[$item['companyId'] ?? 0][] = &$item;
            $childs[$item['companyId'] ?? 0]['cost'] += ($item['price'] ?? 0);
        }

        return $childs;
    }
}

class Company
{
    // Enter your code here

    public function getDataFromApi()
    {
        $url = 'https://5f27781bf5d27e001612e057.mockapi.io/webprovise/companies';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response_json = curl_exec($ch);
        curl_close($ch);
        $items = json_decode($response_json, true);

        return $items;
    }

    public function handleData($items)
    {
        $childs = [];
        foreach ($items as $item) {
            $childs[$item['parentId']][] = $item;
        }
        $tree = $this->createTree($childs, array($items[0]));
        foreach ($tree as &$node) {
            $this->updateCost($node);
        }
        
        return $tree;
    }

    private function createTree(&$list, $parent)
    {
        $tree = [];
        foreach ($parent as $k => $l) {
            if (isset($list[$l['id']])) {
                $l['children'] = $this->createTree($list, $list[$l['id']]);
            }

            $tree[] = $l;
        }
        return $tree;
    }

    private function updateCost(&$node)
    {
        $children = null;
        if (isset($node['children'])) {
            $children = &$node['children'];
        }
        if (isset($children)) {
            foreach ($children as &$child) {
                $node['cost'] += $this->updateCost($child);
            }
        }

        return $node['cost'];
    }
}

class TestScript
{
    public function execute()
    {
        $start = microtime(true);
        // Enter your code here
        // echo json_encode($result);

        $company = new Company();
        $companyData = $company->getDataFromApi();
        $travels = (new Travel())->handleData();
        //matching cost from each ID
        foreach ($travels as $id => $travel) {
            foreach ($companyData as &$item) {
                if ($item['id'] === $id) {
                    $item['cost'] = $travel['cost'] ?? 0;
                }
            }
        }
        $result = $company->handleData($companyData);
        echo json_encode($result);
        // echo 'Total time: '.  (microtime(true) - $start);
    }

    public function handleChildren(&$company, $travels)
    {
        foreach ($company as $child) {
            if (isset($child['children'])) {
                foreach ($child['children'] as $item) {
                    if (is_array($item)) {
                    }
                }
            }
            if (!isset($child['children']) && is_array($child)) {
                if (isset($travels[$child['id']])) {
                    $child['children'] = $travels[$child['id']];
                    return;
                }
            }
            $this->handleChildren($child, $travels);
        }
    }
}

(new TestScript())->execute();
