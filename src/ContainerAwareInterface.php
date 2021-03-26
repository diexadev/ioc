<?php

namespace Folk\Container;

interface ContainerAwareInterface
{
    public function setContainer($container);
	public function getContainer();
}
