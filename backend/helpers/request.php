<?php
function getTask(): ?string
{
  return $_GET['task'] ?? null;
}