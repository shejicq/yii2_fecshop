<?php
/**
 * FecShop file.
 *
 * @link http://www.fecshop.com/
 * @copyright Copyright (c) 2016 FecShop Software LLC
 * @license http://www.fecshop.com/license/
 */

namespace fecshop\app\appserver\modules\Catalog\block\reviewproduct;

//use fecshop\app\apphtml5\modules\Catalog\helpers\Review as ReviewHelper;
use Yii;

/**
 * @author Terry Zhao <2358269014@qq.com>
 * @since 1.0
 */
class Lists
{
    public $product_id;
    public $spu;
    public $filterBySpu = true;
    public $filterOrderBy = 'review_date';
    public $_page = 'p';
    public $numPerPage = 20;
    public $pageNum;
    /**
     * Ϊ�˿���ʹ��rewriteMap��use ������ļ�ͳһ��������ķ�ʽ��ͨ��Yii::mapGet()�õ�className��Object
     */
    protected $_reviewHelperName = '\fecshop\app\apphtml5\modules\Catalog\helpers\Review';
    protected $_reviewHelper;
    
    public function __construct()
    {
        /**
         * ͨ��Yii::mapGet() �õ���д���class�����Լ�����Yii::mapGet�����ļ�@fecshop\yii\Yii.php��
         */
        list($this->_reviewHelperName,$this->_reviewHelper) = Yii::mapGet($this->_reviewHelperName);  
        // ��ʼ������
        $reviewHelper = $this->_reviewHelper;
        $reviewHelper::initReviewConfig();
    }
    /**
     * @property $countTotal | Int
     * �õ�toolbar�ķ�ҳ����
     */
    protected function getProductPage($countTotal)
    {
        if ($countTotal <= $this->numPerPage) {
            return '';
        }
        $config = [
            'class'        => 'fecshop\app\apphtml5\widgets\Page',
            'view'        => 'widgets/page.php',
            'pageNum'        => $this->pageNum,
            'numPerPage'    => $this->numPerPage,
            'countTotal'    => $countTotal,
            'page'            => $this->_page,
        ];

        return Yii::$service->page->widget->renderContent('category_product_page', $config);
    }
    // ��ʼ������
    public function initParam()
    {
        $this->pageNum = Yii::$app->request->get($this->_page);
        $this->pageNum = $this->pageNum ? $this->pageNum : 1;
        //$this->spu = Yii::$app->request->get('spu');
        $this->product_id = Yii::$app->request->get('product_id');
        $review = Yii::$app->getModule('catalog')->params['review'];
        $productPageReviewCount = isset($review['reviewPageReviewCount']) ? $review['reviewPageReviewCount'] : 10;
        $this->numPerPage = $productPageReviewCount ? $productPageReviewCount : $this->numPerPage;
    }

    public function getLastData()
    {
        $this->initParam();
        if (!$this->product_id) {
            return [
                'code' => 401,
                'content' => 'product id is not exist'
            ];
        }
        $product = Yii::$service->product->getByPrimaryKey($this->product_id);
        if (!$product['spu']) {
            return [
                'code' => 401,
                'content' => 'product is not exist'
            ];
        }
        $this->spu = $product['spu'];
        $price_info = $this->getProductPriceInfo($product);
        $spu = $product['spu'];
        $image = $product['image'];
        $main_img = isset($image['main']['image']) ? $image['main']['image'] : '';
        $imgUrl = Yii::$service->product->image->getResize($main_img,[150,150],false);
        
        $name = Yii::$service->store->getStoreAttrVal($product['name'], 'name');
        if ($this->filterBySpu) {
            $data = $this->getReviewsBySpu($this->spu);
            $count = $data['count'];

            $coll = $data['coll'];
            $reviewHelper = $this->_reviewHelper;
            $ReviewAndStarCount = $reviewHelper::getReviewAndStarCount($product);
            list($review_count, $reviw_rate_star_average) = $ReviewAndStarCount;
            $product = [
                'product_id' => $this->product_id,
                'spu' => $this->spu,
                'price_info' => $price_info,
                'imgUrl' => $imgUrl,
                'name' => $name,
            ];
            return [
                'code'          => 200,
                'product'       => $product,
                'reviewList'    => $coll,
                'review_count'              => $review_count,
                'reviw_rate_star_average'   => $reviw_rate_star_average,
            ];
        }
    }
    /**
     * @property $spu  | String
     * ͨ��spu�õ���Ʒ����
     */
    public function getReviewsBySpu($spu)
    {
        $currentIp = \fec\helpers\CFunc::get_real_ip();
        $filter = [
            'numPerPage'    => $this->numPerPage,
            'pageNum'        => $this->pageNum,
            'orderBy'    => [$this->filterOrderBy => SORT_DESC],
            'where'            => [
                [
                    '$or' => [
                        [
                            'status' => Yii::$service->product->review->activeStatus(),
                            'product_spu' => $spu,
                        ],
                        [
                            'status' => Yii::$service->product->review->noActiveStatus(),
                            'product_spu' => $spu,
                            'ip' => $currentIp,
                        ],
                    ],
                ],
            ],
        ];

        return Yii::$service->product->review->getListBySpu($filter);
    }
    // ��Ʒ�۸���Ϣ
    protected function getProductPriceInfo($product)
    {
        $price = $product['price'];
        $special_price = $product['special_price'];
        $special_from = $product['special_from'];
        $special_to = $product['special_to'];

        return Yii::$service->product->price->getCurrentCurrencyProductPriceInfo($price, $special_price, $special_from, $special_to);
    }
}