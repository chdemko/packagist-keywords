<?php

/**
 * This sofware can be used to extract the projects from packagist.org which are linked to the main keywords.
 * 
 * @author     Christophe Demko <chdemko@gmail.com>
 * @copyright  2016 Christophe Demko, France.
 * @license    http://www.cecill.info/licences/Licence_CeCILL-B_V1-en.html CeCILL-B license
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Get the options
$options = getopt('o::m::r::', ['output::', 'max::', 'ratio::']);

// Compute the filename
if (isset($options['o']))
{
	$filename = $options['o'];
}
elseif (isset($options['output']))
{
	$filename = $options['output'];
}
else
{
	date_default_timezone_set('UTC');
	$filename = 'packagist.org-' . date('Y-m-d-H:i:s') . '.csv';
}

// Compute the max number of keywords
if (isset($options['m']))
{
	$max = (int) $options['m'];
}
elseif (isset($options['max']))
{
	$max = (int) $options['max'];
}
else
{
	$max = false;
}

// Compute the ratio
if (isset($options['r']))
{
	$ratio = (float) $options['r'];
}
elseif (isset($options['ratio']))
{
	$ratio = (float) $options['ratio'];
}
else
{
	$ratio = 0;
}

// Get all keywords
$client = new Packagist\Api\Client;
$keywords = [];
$n = 0;

foreach ($client->all() as $key => $name)
{
	echo 'Analyzing ' . $key . '-' . $name . PHP_EOL;

	try
	{
		$package = $client->get($name);
		$versions = $package->getVersions();

		if (!empty($versions))
		{
			$n++;

			foreach (reset($versions)->getKeywords() as $keyword)
			{
				if (!isset($keywords[$keyword]))
				{
					$keywords[$keyword] = 0;
				}

				$keywords[$keyword]++;
			}
		}
	}
	catch (Exception $e)
	{
	}
}

// Sort the keywords
arsort($keywords);

// Keep only max keywords
if ($max)
{
	$keywords = array_slice($keywords, 0, $max, true);
}

// Keep only the keyword with a sufficient ratio
$max = 0;

foreach ($keywords as $keyword => $count)
{
	if ($count < $ratio * $n)
	{
		break;
	}

	$max++;
}

$keywords = array_slice($keywords, 0, $max, true);

// Write the result to a csv file
$handle = fopen($filename, "w");

if ($handle)
{
	fputs($handle, '"",');
	fputcsv($handle, array_keys($keywords));

	foreach ($client->all() as $key => $name)
	{
		echo 'Storing ' . $key . '-' . $name . PHP_EOL;

		try
		{
			$package = $client->get($name);
			$versions = $package->getVersions();

			if (!empty($versions))
			{
				$data = [];
				$data[] = $name;
				$tags = reset($versions)->getKeywords();
				$store = false;

				foreach ($keywords as $keyword => $count)
				{
					$property = in_array($keyword, $tags);
					$store = $store || $property;
					$data[] = (int) $property;
				}

				if ($store)
				{
					fputcsv($handle, $data);
				}
			}
		}
		catch (Exception $e)
		{
		}
	}

	fclose($handle);
}
