<?php

namespace Drupal\custom_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\custom_api\ApiBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * This controller is used to expose the data in API calls.
 */
class CustomApiController extends ControllerBase {
  /**
   * This is instance of ApiBuilder services.
   *
   * @var \Drupal\custom_api\ApiBuilder
   */
  protected ApiBuilder $apiBuilder;

  /**
   * Constructs the CustomApiController object with the required depenency.
   *
   * @param \Drupal\custom_api\ApiBuilder $api_builder
   *   This is instance of ApiBuilder services.
   */
  public function __construct(ApiBuilder $api_builder) {
    $this->apiBuilder = $api_builder;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('custom_api.api_builder'),
    );
  }

  /**
   * This is to build the response for the api call.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Returns the JsonResponse.
   */
  public function buildResult(Request $request) {
    // Fetch the secret key attached in headers.
    $secret_key = $request->headers->get('secretkey');

    // Fetch the news category passed as parameter.
    $tag_name = $request->query->get('tag');

    // Checks the secret key is valid or not.
    if ($secret_key == 'ABCD') {

      // Use the services to fetch the news data.
      $node_data_array = $this->apiBuilder->build();

      // If any tag present then move to the building response otherwise throw
      // an error due to no tag present.
      if ($tag_name) {
        $result['title'] = 'News Node Data';
        $result['data'] = $this->tagBased($node_data_array, $tag_name);
        $result['data count'] = count($result['data']);
      }

      else {
        $result = "No news for the Tag was found.";
      }
    }

    else {
      $result = "No Secret Key found in your request.";
    }

    $response = new JsonResponse($result, 200);
    $response->setEncodingOptions(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return $response;
  }

  /**
   * This method is to filter the data based on tags.
   *
   * @param array $node_data_array
   *   The input data array.
   * @param string $tag_name
   *   The tag name.
   *
   * @return array
   *   Returns the result array.
   */
  public function tagBased(array $node_data_array, string $tag_name) {
    foreach ($node_data_array['data'] as $node) {
      $tags = explode(',', $node['tags']);
      foreach ($tags as $tag) {
        if ($tag == $tag_name) {
          $result[] = $node;
        }
      }
    }
    return $result;
  }

}
