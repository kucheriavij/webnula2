{extends "cms@layouts.base"}

{block content}
	{$form->render()}

	{if $Yii->getUser()->checkAccess('Root')}
		{if $this->action->id === 'update' && !$model->IsNewRecord}
		{/if}
	{/if}
{/block}