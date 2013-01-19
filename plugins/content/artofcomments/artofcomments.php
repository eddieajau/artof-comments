<?php
/**
 * @package  	NewLifeInIT
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
	 * Stores whether comments are enabled for content.
	 *
	 * @var    array
	 * @since  1.1
	 */
	private $_enabled = array();

	/**
	 * Displays the comments after the content.
	 *
	 * Method is called by the view and the results are imploded and displayed in a placeholder
	 *
	 * @param   string   $context     The context for the content passed to the plugin.
	 * @param   object   $acticle     The content object.  Note $article->text is also available.
	 * @param   object   $params      The content params.
	 * @param   integer  $limitstart  The 'page' number.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function onContentAfterDisplay($context, $article, $params, $limitstart = 0)
	{
		if (!$this->_enabled($context, $article))
		{
			return '';
		}

		$providerId = $this->params->get('provider_id');
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
	 * Displays a link to the comments.
	 *
	 * Method is called by the view and the results are imploded and displayed in a placeholder
	 *
	 * @param   string   $context     The context for the content passed to the plugin.
	 * @param   object   $acticle     The content object.  Note $article->text is also available.
	 * @param   object   $params      The content params.
	 * @param   integer  $limitstart  The 'page' number.
	 *
	 * @return  string
	 *
	 * @since   1.1
	 */
	public function onContentBeforeDisplay($context, $article, $params, $limitstart = 0)
	{
		if (!$this->_enabled($context, $article) || !$this->params->get('show_jump'))
		{
			return '';
		}

		$this->loadLanguage();

		$code = '<p><a href="#disqus_thread">' . JText::_('PLG_ARTOFCOMMENTS_JUMP') . '</a></p>';

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
	public function onContentPrepareForm($form, $data)
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

	/**
	 * Checks if comments are enabled for the context and article data.
	 *
	 * @param   string  $context  The context for the content passed to the plugin.
	 * @param   object  $acticle  The content object.  Note $article->text is also available.
	 *
	 * @return  boolean True if comments are enabled for this article, false otherwise.
	 *
	 * @since   1.1
	 */
	private function _enabled($context, $article)
	{
		$providerId = $this->params->get('provider_id');
		$enabledKey = $context . '-' . (int) $article->id;

		if (empty($providerId) || empty($article->id))
		{
			return false;
		}
		elseif (isset($this->_enabled[$enabledKey]))
		{
			return $this->_enabled[$enabledKey];
		}

		try
		{
			// $context gives us no clue about the view we are in.
			$app = JFactory::getApplication();
			$view = $app->input->get('view');

			if ($context != 'com_content.article')
			{
				throw new Exception;
			}

			if ($view != 'article')
			{
				throw new Exception;
			}

			$enabled = $article->params->get('enable_artofcomments');

			// Check the category parameters if the article has no information.
			if ($enabled === null && isset($article->catid) && $article->catid)
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
		}
		catch (Exception $e)
		{
			$enabled = false;
		}

		$this->_enabled[$enabledKey] = $enabled;

		return $enabled;
	}
}
