curl -H'Content-Type: application/json' -XPUT http://localhost:9200/products/_mapping/_doc?pretty -d'{
  "properties": {
    "type": {"type": "keyword"},
    "title": {"type": "text", "analyzer": "ik_smart"},
    "long_title": {"type": "text", "analyzer": "ik_smart"},
    "category_id": {"type": "integer"},
    "category": {"type": "keyword"},
    "category_path": {"type": "keyword"},
    "description": {"type": "text", "analyzer": "ik_smart"},
    "price": {"type": "scaled_float", "scaling_factor": 100},
    "on_sale": {"type": "boolean"},
    "rating": {"type": "float"},
    "sold_count": {"type": "integer"},
    "review_count": {"type": "integer"},
    "skus":{
      "type": "nested",
      "properties": {
        "title": {"type": "text", "analyzer": "ik_smart", "copy_to": "skus_title"},
        "description": {"type": "text", "analyzer": "ik_smart", "copy_to": "skus_description"},
        "price": {"type": "scaled_float", "scaling_factor": 100}
      }
     },
    "properties": {
      "type": "nested",
      "properties": {
        "name": {"type": "keyword"},
       	"value": {"type": "keyword", "copy_to": "properties_name"}
      }
    }
  }
}'
<?php
$params = [
 'index' => 'products',
 'type' => '_doc',
 'body' => [
    'from' => 0,
    'size' => 5,
    'query' => [
      'bool' => [
        'filter' => [
          ['term' => ['on_sale' => true]],
        ],
        'must' => [
          [
            'multi_match' => [
              'query' => '256G',
              'fields' => [
                'skus_title',
                'skus_description',
                'properties_value',
              ],
            ],
          ],
        ],
      ],
    ],
  ],
 ],
];