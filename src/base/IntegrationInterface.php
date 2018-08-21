<?php

namespace ether\out\base;

interface IntegrationInterface
{

	/**
	 * Use to register any elements / sources that would otherwise not exist
	 */
	public static function register ();

	/**
	 * Should return a keyed array of functions that return available Fields
	 * when an element of the keyed type is passed. Should be keyed with the
	 * element class name (`Element::class`).
	 *
	 * @return array
	 */
	public static function fields (): array;

	/**
	 * Returns true if the plugin being integrated is installed.
	 *
	 * @return bool
	 */
	public static function isInstalled (): bool;

}