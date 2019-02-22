+++
title = "Documentation"
description = "Built with Hugo and hosted on Github Pages"
weight = 1
+++

This page describes how to edit the documentation you're currently reading.
The whole documentation is located in the directory `hugo`. Change into this directory.
All following commands are supposed to be run from here.

```
cd hugo
```

## Requirements

Download [Hugo binary](https://gohugo.io/overview/installing/) for your OS (Windows, Linux, Mac).

## Editing

Change files in `hugo/content` to edit the documentation.
See the [DocDock documentation](https://docdock.netlify.com/original/content-organisation/) for more information on how to organize content.
Afterwards, commit your work to your repository.

```
git commit -a
```

## Testing

Run `hugo` locally to see if your content looks like you imagined.

```
hugo server
```
Open [http://localhost:1313/](http://localhost:1313/) in your local browser.

## Publishing

Run the following script to commit to the branch `gh-pages`.
From there, github auto-deploys the documentation.

```
./publish_docs.sh
```
See [Hugo documentation](https://gohugo.io/hosting-and-deployment/hosting-on-github/#deployment-of-project-pages-from-your-gh-pages-branch) for more details.
