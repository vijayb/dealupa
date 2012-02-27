//
//  This script was created
//  by Mircho Mirev
//  mo /mo@momche.net/
//
//	:: feel free to use it BUT
//	:: if you want to use this code PLEASE send me a note
//	:: and please keep this disclaimer intact
//

var cAutocomplete =
{
	sDescription : 'autcomplete class'
}


cAutocomplete.complete = function( hEvent )
{
	if( hEvent == null )
	{
		var hEvent = window.hEvent
	}

	var hElement = ( hEvent.srcElement ) ? hEvent.srcElement : hEvent.originalTarget
	
	var sAA = hElement.getAttribute( 'autocomplete' ).toString()
	if( sAA.indexOf( 'array:' ) >= 0 )
	{
		hArr = eval( sAA.substring( 6 ) )
	}
	else if(  sAA.indexOf( 'list:' ) >= 0 )
	{
		hArr = sAA.substring( 5 ).split( '|' )
	}


	if( hEvent.keyCode == 16 )  
	{
		return
	}
	var sVal = hElement.value.toLowerCase()
	if( hEvent.keyCode == 8 )
	{
		sVal = sVal.substring( 0, sVal.length - 1 )
	}
	if( sVal.length < 1 )
	{
		return
	}
	for( var nI = 0; nI < hArr.length; nI++ )
	{
		sMonth = hArr[ nI ]
		nIdx = sMonth.toLowerCase().indexOf( sVal, 0 )
		if( nIdx == 0 && sMonth.length > sVal.length )
		{
			hElement.value = hArr[ nI ]
			if( hElement.createTextRange )
			{
				hRange = hElement.createTextRange()
				hRange.findText( hArr[ nI ].substr( sVal.length ) )
				hRange.select()
			}
			else
			{
				hElement.setSelectionRange( sVal.length, sMonth.length )
			}
			return
		}
	}
}

cAutocomplete.init = function()
{

	var nI = 0
	var aInputs = document.getElementsByTagName( 'input' )
	for( var nI = 0; nI < aInputs.length; nI ++ )
	{
		if( aInputs[ nI ].type.toLowerCase() == 'text' )
		{
		 	var sLangAtt = aInputs[ nI ].getAttribute( 'autocomplete' )
			if( sLangAtt )
			{
					if( document.attachEvent ) 
					{
						aInputs[ nI ].attachEvent( 'onkeyup', cAutocomplete.complete )
					}
					else if( document.addEventListener )
					{
						aInputs[ nI ].addEventListener( 'keyup', cAutocomplete.complete, false )
					} 
			}
		}
	}
}

if( window.attachEvent ) 
{
	window.attachEvent( 'onload', cAutocomplete.init )
}
else if( window.addEventListener )
{
	window.addEventListener( 'load', cAutocomplete.init, false )
}
					 