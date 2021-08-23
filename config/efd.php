<?php
// some simple config for the efd connector
return [
  'base_url' => 'https://efdsearch.senate.gov',
  'login_path' => '/search/home',
  'search_path' => '/search',
  'ptr_path' => '/search/view/ptr',
  'data_path' => '/search/report/data',
  'cookie_uri' => '/tmp/efdcookies.txt'
];