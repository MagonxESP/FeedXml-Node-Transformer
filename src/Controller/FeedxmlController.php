<?php
/**
 * @file
 * Contains \Drupal\feed_xml\Controller\FeedxmlController.
 */
namespace Drupal\feed_xml\Controller;

use Drupal\views\Views;
use Symfony\Component\HttpFoundation\Response;


class FeedxmlController {

    private $document;

    public function __construct() {
        $this->document = new \DOMDocument();
    }

    public function index() {
        $json = $this->getViewResponse();
        $result = $this->document->createElement('result');
        $result->setAttribute('is_array', 'true');

        foreach ($json as $object) {
            $item = $this->createItem($object);
            $result->appendChild($item);
        }

        $this->document->appendChild($result);

        return new Response($this->document->saveXML(), 200, [
            'Content-Type' => 'text/xml'
        ]);
    }

    private function getViewResponse() {
        $view = Views::getView('all_contents');
        $json_string = $view->render('rest_export_1')['#markup'];
        return json_decode($json_string);
    }

    private function createItem($object) {
        $item = $this->document->createElement('item');
        $elements = $this->createChildElementsObject($object);

        foreach ($elements as $element) {
            $item->appendChild($element);
        }

        return $item;
    }

    /**
     * Transforma las propiedades de un objeto en elementos xml
     *
     * @param $object
     * @return array
     */
    private function createChildElementsObject($object) {
        $child_elements = [];

        foreach ($object as $property => $value) {
            $child_element = $this->document->createElement($property);

            if (is_array($value)) {
                $elements = $this->createChildElementsArray($value);

                foreach ($elements as $element) {
                    $child_element->appendChild($element);
                }
            } else if (is_object($value)) {
                $elements = $this->createChildElementsObject($value);

                foreach ($elements as $element) {
                    $child_element->appendChild($element);
                }
            } else {
                $child_element->nodeValue = strip_tags(htmlspecialchars($value));
            }

            $child_elements[] = $child_element;
        }

        return $child_elements;
    }

    /**
     * Transforma un array en elemetos xml
     *
     * @param $object_array
     * @return array
     */
    private function createChildElementsArray($object_array) {
        $child_elements = [];

        foreach ($object_array as $object) {
            if (is_object($object)) {
                $elements = $this->createChildElementsObject($object);
                $value_element = $this->document->createElement('values');

                foreach ($elements as $element) {
                    $value_element->appendChild($element);
                }

                $child_elements[] = $value_element;
            } else if (is_array($object)) {
                $elements = $this->createChildElementsArray($object);
                $child_elements = array_merge($child_elements, $elements);
            }
        }

        return $child_elements;
    }

}