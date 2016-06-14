<?php

/**
 * This sofware can be used to extract the projects from packagist.org which are linked to the main keywords.
 * 
 * @author     Christophe Demko <chdemko@gmail.com>
 * @copyright  2016 Christophe Demko, France.
 * @copyright  2016 The Galactic Organization, France
 * @license    http://www.cecill.info/licences/Licence_CeCILL-B_V1-en.html CeCILL-B license
 */

require_once 'vendor/autoload.php';

// Get the options
$options = getopt('o::m::r::i', ['output::', 'max::', 'ratio::', 'min::']);

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

// Compute the min number of keywords
if (isset($options['i']))
{
	$min = (int) $options['i'];
}
elseif (isset($options['min']))
{
	$min = (int) $options['min'];
}
else
{
	$min = false;
}

// Get all keywords
$client = new Packagist\Api\Client;
$keywords = [];
$n = 0;
$projects = $client->all();
$count = count($projects);
$bar = new Dariuszp\CliProgressBar($count);
echo 'Pass 1/2: Analyzing projects' . PHP_EOL;
$bar->display();

foreach ($projects as $key => $name)
{
	$bar->progress();

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

// Keep at minimum $min keywords
if ($max < $min)
{
	$max = $min;
}

$keywords = array_slice($keywords, 0, $max, true);
$k = count($keywords);
$bar->end();

// Write the result to a csv file
$handle = fopen($filename, "w");

if ($handle)
{
	$n = 0;
	$bar = new Dariuszp\CliProgressBar($count);
	echo 'Pass 2/2: Saving data' . PHP_EOL;
	$bar->display();

	fputs($handle, '"",');
	fputcsv($handle, array_keys($keywords));

	foreach ($projects as $key => $name)
	{
		$bar->progress();

		try
		{
			$package = $client->get($name);
			$versions = $package->getVersions();

			if (!empty($versions))
			{
				$bar->progress();
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
					$n++;
					fputcsv($handle, $data);
				}
			}
		}
		catch (Exception $e)
		{
		}
	}

	fclose($handle);
	$bar->end();
}

echo "Saving $n projects with $k keywords in file $filename." . PHP_EOL;
