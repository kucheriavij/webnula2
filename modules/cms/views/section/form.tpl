{extends "cms@layouts.base"}

{block content}
	{$this->widget('webnula2\widgets\booster\TbBreadcrumbs', ['links' => $links,'homeLink' => $this->homeLink], true)}

	{$form->render()}
{/block}