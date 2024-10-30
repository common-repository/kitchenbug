<div class="kb-recipe-wrap">

	<div class="kb-recipe-header">
		
		<div class="kb-title-bar clearfix">
		
			<h2 class="kb-name" itemprop="name"><?php echo $this->name ?></h2>

			<div class="kb-subtitle">

				<span>From <a itemprop="publisher" href="<?php echo $this->publisherURL ?>"><?php echo $this->publisherName ?></a></span>
<?php if (!empty($this->category)) : ?>
				 |
				<span itemprop="recipeCategory" class="kb-category"><?php echo $this->category ?></span>
<?php endif; ?>
<?php if (!empty($this->cuisine)) : ?>
				 | 
				<span itemprop="recipeCuisine" class="kb-cuisine"><?php echo $this->cuisine ?></span>
<?php endif; ?>

			</div>
		
		</div>

		<?php if (!$this->isFeed) $this->showActionMenu() ?>

		<div class="kb-header-mid clearfix">

			<?php if (!empty($this->image)) : ?>

			<img itemprop="image" src="<?php echo $this->image ?>" />
			
			<?php endif; ?>

			<p class="kb-description" itemprop="description"><?php echo $this->description ?></p>
		
		</div>
		
		<?php if ($this->totalTime !== '00:00') : ?>
		<div class="kb-times clearfix">

			<div class="kb-time">

				<label>Total</label>
				<span><meta itemprop="totalTime" content="<?php echo $this->totalTimeContent ?>"><?php echo $this->totalTime ?></span>

			</div>

			<div class="kb-time">

				<label>Prep</label>
				<span><meta itemprop="prepTime" content="<?php echo $this->prepTimeContent ?>"><?php echo $this->prepTime ?></span>

			</div>

			<div class="kb-time">

				<label>Cook</label>
				<span><meta itemprop="cookTime" content="<?php echo $this->cookTimeContent ?>"><?php echo $this->cookTime ?></span>

			</div>

		</div>
		<?php endif; ?>
		
	</div>

	<?php if ($this->nutritionOn == 'on') {echo '<div class="kb-nut-highlight">';$this->showNutHighlight();echo	'</div>';}?>
	
	<div class="kb-recipe-body">

		<?php if (!$this->isFeed) : ?>

		<div class="kb-controls clearfix">
	
			<div class="kb-control">
		
				<span itemprop="recipeYield" style="display: none;"><?php echo $this->servings ?> servings</span>
				<label>Servings</label>
				<?php $this->showScaleSelect($this->minServings, $this->maxServings, $this->servings) ?>		
		
			</div>
	
			<div class="kb-control">
		
				<?php $this->showConvertSelect($this->scale) ?>		
		
			</div>
	
		</div>
		
		<?php endif; ?>

		<div class='kb-ingredients-container'>
	
			<h3>Ingredients</h3>

			<ul data-wikilinksOn="<?php if (isset($this->wikilinksOn) ) { echo $this->wikilinksOn;} ?>">
		
				 <?php $this->renderWrappedArray($this->ingredients) ?>
	
			</ul>
	
		</div>

		<div class="kb-directions-container">
	
			<h3>Directions</h3>
	
			<div itemprop="recipeInstructions">

				<ol>
		
					 <?php $this->renderDirectionsArray($this->directions) ?>
		
				</ol>
	
			</div>
	
		</div>

		<?php if ($this->tips && count($this->tips)) : ?>

		<div class="kb-tips-container">
	
			<h3>Tips</h3>
	
		    <ul>
	
				<?php $this->renderWrappedArray($this->tips) ?>
	
			</ul>

		</div>

		<?php endif; ?>
	
	</div>

</div>