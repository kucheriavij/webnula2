<!doctype html>
<html lang="{$Yii->language}">
<head>
	<meta charset="UTF-8">
	{$this->module->registerCssFile('css/auth.css')}
	<title>{$this->module->t('Authorization')} :: {$Yii->params->projectName}</title>
</head>
<body>
<div class="container">

	{form id="login-form" action=["auth/login"] htmlOptions=['class' => 'form-signin']}
		<h2 class="form-signin-heading">{$this->module->t('Sign In')}</h2>
	{$form->textField($model, 'username', ['placeholder' => $this->module->t('Username'), 'class'=>'form-control'])}
	{$form->passwordField($model, 'password', ['placeholder' => $this->module->t('Password'), 'class'=>'form-control'])}
	{$form->error($model, 'password')}
	{$form->checkboxGroup($model, 'rememberMe')}
	{CHtml::submitButton($this->module->t('Log-in'), ['class' => 'btn btn-lg btn-primary btn-block'])}
	{/form}

</div>
<!-- /container -->
</body>
</html>