function smf_NewsFader(oOptions)
{
	this.opt = oOptions;

	this.oFaderHandle = document.getElementById(this.opt.sFaderControlId);

	// Fade from... what text color? Default to black.
	this.oFadeFrom = 'oFadeFrom' in this.opt ? this.opt.oFadeFrom : {
		r: 0,
		g: 0,
		b: 0
	};

	// To which background color? Default to white.
	this.oFadeTo = 'oFadeTo' in this.opt ? this.opt.oFadeTo : {
		r: 255,
		g: 255,
		b: 255
	};

	// Surround each item with... anything special?
	this.sItemTemplate = 'sItemTemplate' in this.opt ? this.opt.sItemTemplate : '%1$s';

	// Fade delay (in milliseconds).
	this.iFadeDelay = 'iFadeDelay' in this.opt ? this.opt.iFadeDelay : 5000;

	// The array that contains all the lines of the news for display.
	this.aFaderItems = 'aFaderItems' in this.opt ? this.opt.aFaderItems : [];

	// Should we look for fader data, still?
	this.bReceivedItemsOnConstruction = 'aFaderItems' in this.opt;

	// The current item in smfFadeContent.
	this.iFadeIndex = -1;

	// Percent of fade (-64 to 510).
	this.iFadePercent = 510

	// Direction (in or out).
	this.bFadeSwitch = false;

	// Just make sure the page is loaded before calling the init.
	setTimeout(this.opt.sSelf + '.init();', 1);
}

function parseColor(color) {

    var cache
      , p = parseInt // Use p as a byte saving reference to parseInt
      , color = color.replace(/\s\s*/g,'') // Remove all spaces
    ;//var
    
    // Checks for 6 digit hex and converts string to integer
    if (cache = /^#([\da-fA-F]{2})([\da-fA-F]{2})([\da-fA-F]{2})/.exec(color)) 
        cache = [p(cache[1], 16), p(cache[2], 16), p(cache[3], 16)];
        
    // Checks for 3 digit hex and converts string to integer
    else if (cache = /^#([\da-fA-F])([\da-fA-F])([\da-fA-F])/.exec(color))
        cache = [p(cache[1], 16) * 17, p(cache[2], 16) * 17, p(cache[3], 16) * 17];
        
    // Checks for rgba and converts string to
    // integer/float using unary + operator to save bytes
    else if (cache = /^rgba\(([\d]+),([\d]+),([\d]+),([\d]+|[\d]*.[\d]+)\)/.exec(color))
        cache = [+cache[1], +cache[2], +cache[3], +cache[4]];
        
    // Checks for rgb and converts string to
    // integer/float using unary + operator to save bytes
    else if (cache = /^rgb\(([\d]+),([\d]+),([\d]+)\)/.exec(color))
        cache = [+cache[1], +cache[2], +cache[3]];
        
    // Otherwise throw an exception to make debugging easier
    else throw Error(color + ' is not supported by $.parseColor');
    
    // Performs RGBA conversion by default
    isNaN(cache[3]) && (cache[3] = 1);
    
    // Adds or removes 4th value based on rgba support
    // Support is flipped twice to prevent erros if
    // it's not defined
    return cache.slice(0,3 + !!$.support.rgba);
}

smf_NewsFader.prototype.init = function init()
{
	var oForeEl, oForeColor, oBackEl, oBackColor;

	// Try to find the fore- and background colors.
	if ('currentStyle' in this.oFaderHandle)
	{
		oForeColor = parseColor(this.oFaderHandle.currentStyle.color);
		this.oFadeFrom = {
			r: parseInt(oForeColor[0]),
			g: parseInt(oForeColor[1]),
			b: parseInt(oForeColor[2])
		};

		oBackEl = this.oFaderHandle;
		while (oBackEl.currentStyle.backgroundColor == 'transparent' && 'parentNode' in oBackEl)
			oBackEl = oBackEl.parentNode;

		oBackColor = parseColor(oBackEl.currentStyle.backgroundColor);
		this.oFadeTo = {
			r: parseInt(oForeColor[0]),
			g: parseInt(oForeColor[1]),
			b: parseInt(oForeColor[2])
		};
	}
	else if (!('opera' in window) && 'defaultView' in document)
	{
		oForeEl = this.oFaderHandle;
		while (document.defaultView.getComputedStyle(oForeEl, null).getPropertyCSSValue('color') == null && 'parentNode' in oForeEl && 'tagName' in oForeEl.parentNode)
			oForeEl = oForeEl.parentNode;

		oForeColor = document.defaultView.getComputedStyle(oForeEl, null).getPropertyValue('color').match(/rgb\((\d+), (\d+), (\d+)\)/);
		this.oFadeFrom = {
			r: parseInt(oForeColor[1]),
			g: parseInt(oForeColor[2]),
			b: parseInt(oForeColor[3])
		};

		oBackEl = this.oFaderHandle;
		while (document.defaultView.getComputedStyle(oBackEl, null).getPropertyCSSValue('background-color') == null && 'parentNode' in oBackEl && 'tagName' in oBackEl.parentNode)
			oBackEl = oBackEl.parentNode;

		oBackColor = document.defaultView.getComputedStyle(oBackEl, null).getPropertyValue('background-color');
		this.oFadeTo = {
			r: parseInt(oBackColor[1]),
			g: parseInt(oBackColor[2]),
			b: parseInt(oBackColor[3])
		};
	}

	// Did we get our fader items on construction, or should we be gathering them instead?
	if (!this.bReceivedItemsOnConstruction)
	{
		// Get the news from the list in boardindex
		var oNewsItems = this.oFaderHandle.getElementsByTagName('li');

		// Fill the array that has previously been created
		for (var i = 0, n = oNewsItems.length; i < n; i ++)
			this.aFaderItems[i] = oNewsItems[i].innerHTML;
	}

	// The ranges to fade from for R, G, and B. (how far apart they are.)
	this.oFadeRange = {
		'r': this.oFadeFrom.r - this.oFadeTo.r,
		'g': this.oFadeFrom.g - this.oFadeTo.g,
		'b': this.oFadeFrom.b - this.oFadeTo.b
	};

	// Divide by 20 because we are doing it 20 times per one ms.
	this.iFadeDelay /= 20;

	// Start the fader!
	window.setTimeout(this.opt.sSelf + '.fade();', 20);
}

// Main	fading function... called 50 times every second.
smf_NewsFader.prototype.fade = function fade()
{
	if (this.aFaderItems.length <= 1)
		return;

	// A fix for Internet Explorer 4: wait until the document is loaded so we can use setInnerHTML().
	if ('readyState' in document && document.readyState != 'complete')
	{
		window.setTimeout(this.opt.sSelf + '.fade();', 20);
		return;
	}

	// Starting out?  Set up the first item.
	if (this.iFadeIndex == -1)
	{
		setInnerHTML(this.oFaderHandle, this.sItemTemplate.replace('%1$s', this.aFaderItems[0]));
		this.iFadeIndex = 1;

		// In Mozilla, text jumps around from this when 1 or 0.5, etc...
		if ('MozOpacity' in this.oFaderHandle.style)
			this.oFaderHandle.style.MozOpacity = '0.90';
		else if ('opacity' in this.oFaderHandle.style)
			this.oFaderHandle.style.opacity = '0.90';
		// In Internet Explorer, we have to define this to use it.
		else if ('filter' in this.oFaderHandle.style)
			this.oFaderHandle.style.filter = 'alpha(opacity=100)';
	}

	// Are we already done fading in?  If so, fade out.
	if (this.iFadePercent >= 510)
		this.bFadeSwitch = !this.bFadeSwitch;

	// All the way faded out?
	else if (this.iFadePercent <= -64)
	{
		this.bFadeSwitch = !this.bFadeSwitch;

		// Go to the next item, or first if we're out of items.
		setInnerHTML(this.oFaderHandle, this.sItemTemplate.replace('%1$s', this.aFaderItems[this.iFadeIndex ++]));
		if (this.iFadeIndex >= this.aFaderItems.length)
			this.iFadeIndex = 0;
	}

	// Increment or decrement the fade percentage.
	if (this.bFadeSwitch)
		this.iFadePercent -= 255 / this.iFadeDelay * 2;
	else
		this.iFadePercent += 255 / this.iFadeDelay * 2;

	// If it's not outside 0 and 256... (otherwise it's just delay time.)
	if (this.iFadePercent < 256 && this.iFadePercent > 0)
	{
		// Easier... also faster...
		var tempPercent = this.iFadePercent / 255, rounded;

		if ('MozOpacity' in this.oFaderHandle.style)
		{
			rounded = Math.round(tempPercent * 100) / 100;
			this.oFaderHandle.style.MozOpacity = rounded == 1 ? '0.99' : rounded;
		}
		else if ('opacity' in this.oFaderHandle.style)
		{
			rounded = Math.round(tempPercent * 100) / 100;
			this.oFaderHandle.style.opacity = rounded == 1 ? '0.99' : rounded;
		}
		else
		{
			var done = false;
			if ('alpha' in this.oFaderHandle.filters)
			{
				try
				{
					this.oFaderHandle.filters.alpha.opacity = Math.round(tempPercent * 100);
					done = true;
				}
				catch (err)
				{
				}
			}

			if (!done)
			{
				// Get the new R, G, and B. (it should be bottom + (range of color * percent)...)
				var r = Math.ceil(this.oFadeTo.r + this.oFadeRange.r * tempPercent);
				var g = Math.ceil(this.oFadeTo.g + this.oFadeRange.g * tempPercent);
				var b = Math.ceil(this.oFadeTo.b + this.oFadeRange.b * tempPercent);

				// Set the color in the style, thereby fading it.
				this.oFaderHandle.style.color = 'rgb(' + r + ', ' + g + ', ' + b + ')';
			}
		}
	}

	// Keep going.
	window.setTimeout(this.opt.sSelf + '.fade();', 20);
}