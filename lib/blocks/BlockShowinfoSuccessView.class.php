<?php
class users_BlockShowinfoSuccessView extends block_BlockView
{
    public function initialize($context, $request)
    {
        $this->disableCache();
    }

 	/**
	 * @param block_BlockContext $context
	 * @param block_BlockRequest $request
	 */
    public function execute($context, $request)
    {
        $this->setTemplateName('Users-Block-Showinfo-Success');
        if ($this->hasParameter('anonymousUser'))
        {
        	$this->setAttribute('anonymousUser', $this->getParameter('anonymousUser'));
        }
        else
        {
        	$this->setAttribute('currentUser', $this->getParameter('currentUser'));
        }
        $this->setAttribute('currentUserParam', $this->getParameter('currentUserParam'));
        
        /* Sample usage of currentUserParam in template (currentUserParam is stored in session)
		<p tal:condition="currentUserParam" tal:repeat="data currentUserParam">
			  <span tal:condition="php: repeat.data.key EQ 'LastTime'" tal:content="data"/>
		</p>
		*/
    }
}