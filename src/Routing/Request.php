<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Application;

use Nette;


/**
 * Presenter request. Immutable object.
 */
class Request
{
	use Nette\SmartObject;

	/** method */
	const FORWARD = 'FORWARD';

	/** flag */
	const SECURED = 'secured';

	/** flag */
	const RESTORED = 'restored';

	/** @var string|NULL */
	private $method;

	/** @var array */
	private $flags = [];

	/** @var string */
	private $name;

	/** @var array */
	private $params;

	/** @var array */
	private $post;

	/** @var array */
	private $files;


	/**
	 * @param  string  fully qualified presenter name (module:module:presenter)
	 * @param  array $params   variables provided to the presenter usually via URL
	 * @param  array $post   variables provided to the presenter via POST
	 * @param  array $files   all uploaded files
	 */
	public function __construct(string $name, string $method = NULL, array $params = [], array $post = [], array $files = [], array $flags = [])
	{
		$this->name = $name;
		$this->method = $method;
		$this->params = $params;
		$this->post = $post;
		$this->files = $files;
		$this->flags = $flags;
	}


	/**
	 * Sets the presenter name.
	 * @return static
	 */
	public function setPresenterName(string $name)
	{
		$this->name = $name;
		return $this;
	}


	/**
	 * Retrieve the presenter name.
	 */
	public function getPresenterName(): string
	{
		return $this->name;
	}


	/**
	 * Sets variables provided to the presenter.
	 * @return static
	 */
	public function setParameters(array $params)
	{
		$this->params = $params;
		return $this;
	}


	/**
	 * Returns all variables provided to the presenter (usually via URL).
	 */
	public function getParameters(): array
	{
		return $this->params;
	}


	/**
	 * Sets variables provided to the presenter via POST.
	 * @return static
	 */
	public function setPost(array $params)
	{
		$this->post = $params;
		return $this;
	}


	/**
	 * Returns all variables provided to the presenter via POST.
	 */
	public function getPost(): array
	{
		return $this->post;
	}


	/**
	 * Sets all uploaded files.
	 * @return static
	 */
	public function setFiles(array $files)
	{
		$this->files = $files;
		return $this;
	}


	/**
	 * Returns all uploaded files.
	 */
	public function getFiles(): array
	{
		return $this->files;
	}


	/**
	 * Sets the method.
	 * @return static
	 */
	public function setMethod(?string $method)
	{
		$this->method = $method;
		return $this;
	}


	/**
	 * Returns the method.
	 */
	public function getMethod(): ?string
	{
		return $this->method;
	}


	/**
	 * Checks if the method is the given one.
	 */
	public function isMethod(string $method): bool
	{
		return strcasecmp($this->method, $method) === 0;
	}


	/**
	 * Sets the flag.
	 * @return static
	 */
	public function setFlag(string $flag, bool $value = TRUE)
	{
		$this->flags[$flag] = $value;
		return $this;
	}


	/**
	 * Checks the flag.
	 */
	public function hasFlag(string $flag): bool
	{
		return !empty($this->flags[$flag]);
	}

}
