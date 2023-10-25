<?php

const TRAVEL_URL = 'https://5f27781bf5d27e001612e057.mockapi.io/webprovise/travels';
const COMPANY_URL = 'https://5f27781bf5d27e001612e057.mockapi.io/webprovise/companies';

class Travel
{
    // Enter your code here


    /**
     * get raw data from REST Api travels.
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
     * handle data and count cost for each company id.
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
    /**
     * get raw data from REST Api companies.
     *
     * @return array
     */
    public function getDataFromApi()
    {
        $url = COMPANY_URL;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response_json = curl_exec($ch);
        curl_close($ch);
        $items = json_decode($response_json, true);

        return $items;
    }

    /**
     * making tree company with cost.
     *
     * @param array $items
     * @return array
     */
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

    /**
     * create tree array.
     *
     * @param array $list
     * @param array $parent
     * @return array
     */
    private function createTree(&$list, $parent)
    {
        $tree = [];
        foreach ($parent as $item) {
            if (isset($list[$item['id']])) {
                $item['children'] = $this->createTree($list, $list[$item['id']]);
            }
            $tree[] = $item;
        }

        return $tree;
    }

     /**
     * update cost for every node tree.
     *
     * @param array $node
     * @return array
     */
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
        echo 'Total time: '.  (microtime(true) - $start);
    }
}

(new TestScript())->execute();
