<?php

class Sidane_Threadmarks_Route_PrefixAdmin_ThreadmarkCategories implements XenForo_Route_Interface
{
  public function match(
    $routePath,
    Zend_Controller_Request_Http $request,
    XenForo_Router $router
  )
  {
    $action = $router->resolveActionWithIntegerParam(
      $routePath,
      $request,
      'threadmark_category_id'
    );

    return $router->getRouteMatch(
      'Sidane_Threadmarks_ControllerAdmin_ThreadmarkCategory',
      $action,
      'sidane_tm_categories'
    );
  }

  public function buildLink(
    $originalPrefix,
    $outputPrefix,
    $action,
    $extension,
    $data,
    array &$extraParams
  )
  {
    return XenForo_Link::buildBasicLinkWithIntegerParam(
      $outputPrefix,
      $action,
      $extension,
      $data,
      'threadmark_category_id'
    );
  }
}
