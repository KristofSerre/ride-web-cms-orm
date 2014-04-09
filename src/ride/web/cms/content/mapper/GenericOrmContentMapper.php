<?php

namespace ride\web\cms\content\mapper;

use ride\library\cms\exception\CmsException;
use ride\library\cms\node\NodeModel;
use ride\library\cms\node\Node;
use ride\library\orm\model\data\format\DataFormatter;
use ride\library\orm\model\Model;
use ride\library\widget\WidgetProperties;

use ride\web\cms\orm\ContentProperties;

/**
 * Content mapper for models defined with the detail widget
 */
class GenericOrmContentMapper extends OrmContentMapper {

    /**
     * Property for the URL
     * @var string
     */
    const PROPERTY_URL = 'url';

	/**
	 * Node containing the detail widget
	 * @var \ride\library\cms\node\Node
	 */
	protected $node;

	/**
	 * Widget properties of the detail widget
	 * @var \ride\library\widget\WidgetProperties
	 */
	protected $properties;

	/**
	 * Parsed arguments of the widget
	 * @var array
	 */
	protected $arguments;

	/**
	 * Constructs a new content mapper for a detail widget
	 * @param \ride\library\cms\node\NodeModel $nodeModel
	 * @param \ride\library\orm\model\Model $model
	 * @param \ride\library\cms\node\Node $node
	 * @param \ride\library\widget\WidgetProperties $properties
	 * @return null
	 */
	public function __construct(NodeModel $nodeModel, Node $node, Model $model, DataFormatter $dataFormatter, WidgetProperties $properties) {
	    parent::__construct($nodeModel, $model, $dataFormatter);

	    $this->node = $node;
	    $this->properties = $properties;

	    $this->arguments = array();
	}

	/**
	 * Get a generic content object for the provided data
	 * @param mixed $data data object of the model or the id of a data object
	 * @return \ride\library\cms\content\Content Generic content object
	 */
	public function getContent($site, $locale, $data) {
	    if ($data === null) {
	        throw new CmsException('Could not get content: provided data is empty');
	    }

	    $index = $site . '-' . $locale;
	    if (!isset($this->arguments[$index])) {
	        $this->parseArguments($index, $site, $locale);
	    }

	    $recursiveDepth = $this->arguments[$index][ContentProperties::PROPERTY_RECURSIVE_DEPTH];
	    $includeUnlocalized = $this->arguments[$index][ContentProperties::PROPERTY_INCLUDE_UNLOCALIZED];
	    $idField = $this->arguments[$index][ContentProperties::PROPERTY_ID_FIELD];

	    $data = $this->getData($site, $locale, $recursiveDepth, $includeUnlocalized, $data, $idField);
	    if (!$data) {
	        return null;
	    }

        $id = $this->reflectionHelper->getProperty($data, $idField);
	    if ($id) {
	        $url = $this->arguments[$index][self::PROPERTY_URL] . $id;
	    } else {
	        $url = null;
	    }

	    $titleFormat = $this->arguments[$index][ContentProperties::PROPERTY_FORMAT_TITLE];
	    $teaserFormat = $this->arguments[$index][ContentProperties::PROPERTY_FORMAT_TEASER];
	    $imageFormat = $this->arguments[$index][ContentProperties::PROPERTY_FORMAT_IMAGE];
	    $dateFormat = $this->arguments[$index][ContentProperties::PROPERTY_FORMAT_DATE];

	    return $this->getContentFromData($data, $url, $titleFormat, $teaserFormat, $imageFormat, $dateFormat);
	}

	/**
	 * Loads the properties of the detail widget for the provided site and locale
	 * @param string $index The key to store the values
	 * @param string $site Id of the site
	 * @param string $locale Code of the current locale
	 * @return null
	 */
	protected function parseArguments($index, $site, $locale) {
	    $properties = new ContentProperties();
	    $properties->getFromWidgetProperties($this->properties, $locale);

	    $this->reflectionHelper = $this->model->getReflectionHelper();

	    $this->arguments[$index] = array(
            ContentProperties::PROPERTY_RECURSIVE_DEPTH => $properties->getRecursiveDepth(),
            ContentProperties::PROPERTY_INCLUDE_UNLOCALIZED => $properties->getIncludeUnlocalized(),
            ContentProperties::PROPERTY_ID_FIELD => $properties->getIdField(),
            ContentProperties::PROPERTY_FORMAT_TITLE => $properties->getContentTitleFormat(),
            ContentProperties::PROPERTY_FORMAT_TEASER => $properties->getContentTeaserFormat(),
            ContentProperties::PROPERTY_FORMAT_IMAGE => $properties->getContentImageFormat(),
            ContentProperties::PROPERTY_FORMAT_DATE => $properties->getContentDateFormat(),
            self::PROPERTY_URL => rtrim($this->baseScript . $this->node->getRoute($locale), '/') . '/',
        );
	}

}
