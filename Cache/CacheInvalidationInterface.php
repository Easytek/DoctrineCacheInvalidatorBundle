<?php

namespace Easytek\DoctrineCacheInvalidatorBundle\Cache;

interface CacheInvalidationInterface
{
	/**
	 * @return array
	 */
	public function getClasses();
}