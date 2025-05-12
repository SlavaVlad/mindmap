# MindMap - Collaborative Infinite Canvas for Nextcloud

A Nextcloud application that provides a collaborative infinite canvas for mindmap creation. Users can create multiple mindmaps on the same canvas, add nodes with rich text and images, and collaborate in real-time.

## Features

- **Infinite Canvas**: Pan and zoom for drawing and viewing mindmaps
- **Multiple Mindmaps**: Create and place multiple independent mindmaps on the same canvas
- **Rich Content**: Nodes support rich text and image uploads
- **Real-Time Collaboration**: See other users' cursors and edit content together
- **Node Locking**: When a user selects a node, it's locked for editing by others
- **Auto-Save**: Changes are automatically saved to the user's Nextcloud files storage

## Installation

### Requirements

- Nextcloud 29+
- PHP 8.0+
- Node.js 20.0.0+ and npm 10.0.0+

### Development Setup

1. Clone the repository into your Nextcloud apps directory:
   ```bash
   cd /path/to/nextcloud/apps/
   git clone https://github.com/SlavaVlad/mindmap.git
   cd mindmap
   ```

2. Install PHP dependencies:
   ```bash
   composer install
   ```

3. Install JavaScript dependencies and build assets:
   ```bash
   npm install
   npm run build
   ```

4. Enable the app in Nextcloud:
   ```bash
   cd /path/to/nextcloud
   php occ app:enable mindmap
   ```

### WebSocket Server Setup (Required for Real-time Collaboration)

For real-time collaboration, you need to set up a WebSocket server. You can use the y-websocket server:

1. Install the y-websocket server globally:
   ```bash
   npm install -g y-websocket
   ```

2. Run the WebSocket server:
   ```bash
   y-websocket-server
   ```

3. Configure the WebSocket URL in your Nextcloud admin settings:
   - Go to Admin settings > MindMap
   - Set the WebSocket URL to `wss://your-server.com/mindmap-ws` or use a proxy

#### Production Setup with Nginx Reverse Proxy

For production, you should set up a reverse proxy to your WebSocket server. Here's an example Nginx configuration:

```nginx
location /mindmap-ws {
    proxy_pass http://localhost:1234;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
}
```

## Usage

1. Access the MindMap app from your Nextcloud dashboard
2. Create a new mindmap or open an existing one
3. Use the toolbar to add nodes and mindmaps to the canvas
4. Double-click on a node to edit its content
5. Use the rich text editor to format text and add images
6. Share the mindmap with other users to collaborate in real-time
7. All changes are automatically saved to your Nextcloud files

## Development

### Building Assets

- To build assets once:
  ```bash
  npm run build
  ```

- For development with watch mode:
  ```bash
  npm run watch
  ```

### Running Tests

- Run PHP tests:
  ```bash
  ./vendor/bin/phpunit
  ```

- Run JavaScript tests:
  ```bash
  npm run test
  ```

## License

This project is licensed under the AGPL-3.0 License.

## Credits

- [React Flow](https://reactflow.dev/) (used via Vue Flow wrapper)
- [Yjs](https://github.com/yjs/yjs) for CRDT-based collaboration
- [TipTap](https://tiptap.dev/) for rich text editing
- [Nextcloud](https://nextcloud.com/) for the platform

## Resources

### Documentation for developers:

- General documentation and tutorials: https://nextcloud.com/developer
- Technical documentation: https://docs.nextcloud.com/server/latest/developer_manual

### Help for developers:

- Official community chat: https://cloud.nextcloud.com/call/xs25tz5y
- Official community forum: https://help.nextcloud.com/c/dev/11
