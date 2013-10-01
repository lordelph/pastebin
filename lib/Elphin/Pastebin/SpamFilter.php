<?php

namespace Elphin\Pastebin;

/**
* Placeholder for your own spam rules
*/
class SpamFilter
{
	public function canPost($text)
	{
		return true;
	}

}