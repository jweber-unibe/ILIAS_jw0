<?php

/* Copyright (c) 2016 Richard Klees <richard.klees@concepts-and-training.de> Extended GPL, see docs/LICENSE */

namespace ILIAS\UI\Implementation\Counter;

use ILIAS\UI\Implementation\AbstractComponentRenderer;
use ILIAS\UI\Renderer as RendererInterface;
use ILIAS\UI\Component\Component;

class Renderer extends AbstractComponentRenderer {
	/**
	 * @inheritdocs
	 */
	public function render(Component $component, RendererInterface $default_renderer) {
		$this->checkComponent($component);

		$tpl = $this->getTemplate("tpl.counter.html", true, true);
		$tpl->setCurrentBlock($component->getType());
		$tpl->setVariable("NUMBER", $component->getNumber());
		$tpl->parseCurrentBlock();

		return $tpl->get();
	}

	/**
	 * @inheritdocs
	 */
	protected function getComponentInterfaceName() {
		return "\\ILIAS\\UI\\Component\\Counter\\Counter";
	}
}
