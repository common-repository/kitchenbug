<div class="kb-recipe-wrap">

	<div class="kb-recipe-header">

		<div class="kb-title-bar clearfix">
		
			<h2 class="kb-name"><?php echo $this->name ?></h2>

			<div class="kb-subtitle">

				<span>From <?php echo $this->publisherName ?></span>
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

		<div class="kb-header-mid clearfix">

			<?php if (!empty($this->image)) : ?>

			<img src="<?php echo $this->image ?>" />
			
			<?php endif; ?>

			<p class="kb-description"><?php echo $this->description ?></p>
		
		</div>
		
		<?php if ($this->totalTime !== '00:00') : ?>
		<div class="kb-times clearfix">

			<div class="kb-time">

				<label>Prep</label>
				<span><?php echo $this->prepTime ?></span>

			</div>

			<div class="kb-time">

				<label>Cook</label>
				<span><?php echo $this->cookTime ?></span>

			</div>

			<div class="kb-time">

				<label>Total</label>
				<span><?php echo $this->totalTime ?></span>

			</div>

		</div>
		<?php endif; ?>
		
	</div>

	<?php if ($this->nutritionOn == 'on') {echo '<div class="kb-nut-highlight">';$this->showNutHighlight();echo	'</div>';}?>
	
	<div class="kb-recipe-body">

		<div class="kb-controls">
	
			<div class="kb-control">
		
				<label>Servings <?php echo $this->servings ?></label>
		
			</div>
	
		</div>

		<div class='kb-ingredients-container'>
	
			<h3>Ingredients</h3>
	
			<ul>
		
				 <?php $this->renderWrappedArray($this->ingredients) ?>
	
			</ul>
	
		</div>

		<div class="kb-directions-container">
	
			<h3>Directions</h3>
	
			<div>

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