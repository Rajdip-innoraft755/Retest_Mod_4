<?php

namespace Drupal\custom_api;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * This service is to build an exposed API of the news type of node.
 */
class ApiBuilder {

  /**
   * This is store the file entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $file;

  /**
   * This is store the data of all nodes of the required type.
   *
   * @var array
   */
  protected array $nodeData;

  /**
   * Constructs an ApiBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->file = $entity_type_manager->getStorage('file');
    $this->nodeData = $entity_type_manager->getStorage('node')->loadByProperties([
      // Fetch the news type node only.
      'type' => 'news',

      // Fetch the published node only.
      'status' => 1,
    ]);
  }

  /**
   * This method is to structure the Api response with required data.
   */
  public function build() {
    foreach ($this->nodeData as $node) {
      $tags = '';

      // Fetch the attached taxonomy terms and makes a
      // comma separeted string with those.
      foreach ($node->get('field_news_tags')->referencedEntities() as $tag) {
        if (strlen($tags) == 0) {
          $tags = $tag->label();
        }
        else {
          $tags = $tags . ',' . $tag->label();
        }
      }

      // Fetch the details of each of the news images and stores those
      // in an array.
      $images = [];
      foreach ($node->field_news_image as $image) {
        $target_id = $image->target_id;
        $target_file = $this->file->load($target_id);

        $image = [
          'title' => $image->title,
          'alt' => $image->alt,
          'height' => $image->height,
          'width' => $image->width,
          'target_id' => $image->target_id,
          'url' => 'http://retest.com' . $target_file->createFileUrl(),
        ];
        $images[] = $image;
      }

      // Builds the actual array of node data with all the details of node.
      $node_data_array['data'][] = [
        'Title' => $node->label(),
        'publication date' => date('Y-m-d', strtotime($node->get('field_publication_date')->value)),
        'tags' => $tags,
        'summary' => $node->body->value,
        'body' => $node->body->value,
        'images' => $images,
        'view count' => $node->field_view_count->value ?? 0,
      ];
    }
    return $node_data_array;
  }

}
