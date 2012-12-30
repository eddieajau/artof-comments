<?php
/**
 * @package  	  NewLifeInIT
 * @subpackage  plg_content_artofcomments
 * @copyright   Copyright (C) 2005 - 2013 New Life in IT Pty Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later when included with or used in the Joomla CMS.
 * @license     MIT when not included with or used in the Joomla CMS.
 */

// No direct access
defined('_JEXEC') or die;

/**
 * ArtofComment content plugin for Discus comments.
 *
 * @package		NewLifeInIT
 * @subpackage	plg_content_artofcomments
 * @since       1.0
 * @link        http://disqus.com
 */
class plgContentArtofcomments extends JPlugin
{
	/**
	 * Displays the comments after the content.
	 *
	 * Method is called by the view and the results are imploded and displayed in a placeholder
	 *
	 * @param   string   $context     The context for the content passed to the plugin.
	 * @param   object   &$acticle    The content object.  Note $article->text is also available.
	 * @param   object   &$params     The content params.
	 * @param   integer  $limitstart  The 'page' number.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function onContentAfterDisplay($context, &$article, &$params, $limitstart)
	{
		$providerId = $this->params->get('provider_id');
		$enabled = $article->params->get('enable_artofcomments');

		if (empty($providerId) || empty($article->id))
		{
			return '';
		}

		// Check the category parameters if the article has no information.
		if ($enabled === null && $article->catid)
		{
			$db = JFactory::getDbo();
			$q = $db->getQuery(true);
			$q->select($q->qn('params'))
				->from($q->qn('#__categories'))
				->where(sprintf('%s = %d', $q->qn('id'), $article->catid));
			$temp = json_decode((string) $db->setQuery($q)->loadResult());

			if (isset($temp->enable_artofcomments))
			{
				$enabled = $temp->enable_artofcomments;
			}
		}

		if (!$enabled)
		{
			return '';
		}

		$code = '<div id="disqus_thread"></div>'
			. '<script type="text/javascript">'
			. sprintf("var disqus_shortname = '%s';", JFilterOutput::cleanText($providerId))
			. sprintf("var disqus_developer = %d;", $this->params->get('developer', 0))
			. sprintf("var disqus_identifier = '/joomla/%s/%d';", JFilterOutput::cleanText($context), $article->id)
// 			. sprintf("var disqus_title = '%s';", $article->title)
			. '(function() {'
			. "var dsq = document.createElement('script'); dsq.type = 'text/javascript'; dsq.async = true;"
			. "dsq.src = 'http://' + disqus_shortname + '.disqus.com/embed.js';"
			. "(document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(dsq);"
			. '})();'
			. '</script>'
			. '<noscript>Please enable JavaScript to view the <a href="http://disqus.com/?ref_noscript">comments powered by Disqus.</a></noscript>'
    		. '<a href="http://disqus.com" class="dsq-brlink">comments powered by <span class="logo-disqus">Disqus</span></a>';

		return $code;
	}

	/**
	 * Prepares an article form or an article category form.
	 *
	 * @param   JForm  $form  The form to be altered.
	 * @param   array  $data  The associated data for the form.
	 *
	 * @return  boolean
	 * @since   1.0
	 */
	function onContentPrepareForm($form, $data)
	{
		if (!($form instanceof JForm))
		{
			$this->_subject->setError('JERROR_NOT_A_FORM');
			return false;
		}

		// Check we are working with a valid form.
		$name = $form->getName();
		$forms = array(
			'com_content.article' => 'attribs',
			'com_categories.categorycom_content' => 'params',
		);

		if (!isset($forms[$name]))
		{
			return true;
		}

		$this->loadLanguage();

		JForm::addFormPath(__DIR__ . '/forms');
		$form->loadFile($forms[$name], false);

		return true;
	}
}
