// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

import { Spinner } from 'components/spinner';
import { observer } from 'mobx-react';
import Message from 'models/chat/message';
import * as React from 'react';
import { classWithModifiers } from 'utils/css';

interface Props {
  message: Message;
}

@observer
export default class MessageItem extends React.Component<Props> {
  render() {
    return (
      <div className={classWithModifiers('chat-message-item', { sending: !this.props.message.persisted })}>
        <div className='chat-message-item__entry'>
          {this.props.message.isHtml ? (
            <div className='osu-md osu-md--chat'>
              {this.renderContent()}
            </div>
          ) : this.renderContent()}
          {!this.props.message.persisted && !this.props.message.errored &&
            <div className='chat-message-item__status'>
              <Spinner />
            </div>
          }
          {this.props.message.errored &&
            <div className='chat-message-item__status chat-message-item__status--errored'>
              <i className='fas fa-times' />
            </div>
          }
        </div>
      </div>
    );
  }

  private renderContent() {
    return (
      <span
        className={classWithModifiers('chat-message-item__content', { action: this.props.message.isAction })}
        dangerouslySetInnerHTML={{ __html: this.props.message.parsedContent }}
      />
    );
  }
}
