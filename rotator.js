function HMSTestimonialRotate(el) {

	this.rotator = el;
	this.timeout = null;
	this.items = this.rotator.find('.hms-testimonial-items > div');
	this.container = this.rotator.find('.hms-testimonial-container');
	this.index = 1;
	this.isplaying = 0;
	this.seconds = 5000;
	this.pauseText = 'Pause';
	this.playText = 'Play';

	var pause_text = el.attr('data-pause-text'),
		play_text = el.attr('data-play-text'),
		seconds = el.attr('data-seconds'),
		start = el.attr('data-start');

	if ( typeof pause_text !== 'undefined' && pause_text !== false ) {
		this.pauseText = pause_text;
	}
	if ( typeof play_text !== 'undefined' && play_text !== false ) {
		this.playText = play_text;
	}
	if ( typeof seconds !== 'undefined' && seconds !== false ) {
		this.seconds = parseInt( seconds * 1000 );
	}

	this.initRotating();

	/**
	 * If we want to start the rotating on load
	 **/
	if ( typeof start !== 'undefined' && start !== false ) {
		if (parseInt(start) == 1) {
			if ( this.rotator.find('.controls .playpause').length == 0 ) {
				this.start();
			} else {
				this.rotator.find('.controls .playpause').trigger('click');
			}
		}
	}
	
}

HMSTestimonialRotate.prototype = {

	initRotating: function() {

		var obj = this;

		obj.rotator.find('.controls .playpause').text( obj.playText );

		obj.rotator.find('.controls .prev').click(function() {
			obj.prev();
			return false;
		});

		obj.rotator.find('.controls .next').click(function() {
			obj.next();
			return false;
		});

		obj.rotator.find('.controls .playpause').click(function() {

			if ( obj.isplaying == 1 ) {

				obj.pause();
				obj.isplaying = 0;
				jQuery(this).text( obj.playText ).removeClass('pause').addClass('play');

			} else {

				obj.start();
				obj.isplaying = 1;
				jQuery(this).text( obj.pauseText ).removeClass('play').addClass('pause');

			}

			return false;
		});

	},

	/**
	 * Move forward 1 item
	 **/
	next: function() {
		var obj = this,
			nextItem = obj.items.get(obj.index);

		if (nextItem == undefined) {
			obj.index = 0;
			nextItem = obj.items.get( obj.index );
		}

		obj.container.fadeOut('slow', function() {
			jQuery(this).html( nextItem.innerHTML);
		}).fadeIn();

		obj.index = obj.index + 1;

		if (obj.isplaying == 1) {
			clearInterval( obj.timeout );
			obj.start();
		}
	},

	/**
	 * Move backward 1 item
	 **/
	prev: function() {
		
		var obj = this,
			newIndex = obj.index - 2,
			nextItem = null;

		if (newIndex < 0) {
			newIndex = (obj.items.length - 1);
		}

		nextItem = obj.items.get(newIndex);
		if (nextItem == undefined) {
			obj.index = 0;
			nextItem = obj.items.get( obj.index );
		}

		obj.container.fadeOut('slow', function() {
			jQuery(this).html( nextItem.innerHTML);
		}).fadeIn();


		obj.index = newIndex + 1;

		if (obj.isplaying == 1) {
			clearInterval( this.timeout );
			this.start();
		}

	},

	/**
	 * Start the rotating
	 **/
	start: function() {

		var obj = this;

		this.timeout = setInterval(function() {

			var nextItem = obj.items.get( obj.index );

			if (nextItem == undefined) {

				obj.index = 0;
				nextItem = obj.items.get( obj.index );
			}

			

			obj.container.fadeOut('slow', function() {
				jQuery(this).html( nextItem.innerHTML );
			}).fadeIn();

			obj.index = obj.index + 1;
			
		}, obj.seconds);
	},

	/**
	 * Pause the rotating
	 **/
	pause: function() {

		this.isplaying = 0;
		clearInterval( this.timeout );

	}

}

jQuery(document).ready(function() {

	jQuery('.hms-testimonials-rotator').each(function() {
		new HMSTestimonialRotate( jQuery(this) );
	});

});