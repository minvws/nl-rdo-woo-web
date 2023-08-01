const { promises: fs } = require('fs')

module.exports = async function commentUpdate({github, context, core, filename }) {
  const {
    issue: { number: issue_number },
    repo: { owner, repo }
  } = context;

  const comment_id = await findComment({
    github,
    core,
    owner,
    repo,
    issue_number,
  }, 'PHPUnit results', 'github-actions[bot]');

  const comment = await fs.readFile(filename, 'utf8');

  const body = 'PHPUnit results for ' + context.workflow + '\n\n' + comment

  if (comment_id === undefined) {
    github.rest.issues.createComment({ issue_number, owner, repo, body })
  } else {
    github.rest.issues.updateComment({ comment_id, owner, repo, body })
  }
}

async function findComment({github, core, owner, repo, issue_number}, body_to_match, comment_user) {
  for await (const {data: comments} of github.paginate.iterator(
    github.rest.issues.listComments, {
      owner,
      repo,
      issue_number
    })
  ) {
    const comment = comments.find(comment => { return comment.user.login === comment_user && comment.body.includes(body_to_match); });
    if (comment) return comment.id
  }

  // Nothing found
  return undefined;
}
