#include <stdio.h>
#include <stdlib.h>
#include <math.h>

typedef struct node *position;
typedef int elementTp;//这样的好处是，如果要换二叉树存储的类型，在这里换以下就可以了

//二叉树节点结构
struct node {
	int  element;
	position p_index;
	position  l_index;
	position r_index;
	//没有指定父节点指针
};

typedef struct node *tree;

tree insert_node(tree t,int num);//这里不知道怎么用指针实现
void insert_node_nempty(tree t,position np);
position find_node(tree t,int num);
position min_node(tree t);
position max_node(tree t);
int delete_node(position np);
static int is_leaf(position np);
static int is_root(position np);
static int delete_leaf(position np);
int count_node(tree t);
int depth(tree t);
int is_avl(tree t);
void swap_avl(tree t,position parent,position np);
void reset_avl(tree t);

//二叉搜索树
//这里没有考虑到重复的问题 todo

void main(){
	int arr[10]={3,9,6,8,5,4,2,1,7,55};
	//int arr[3]={3,9,6};
	int count=10;
	int i;

	tree t=NULL;
	//新增节点
	for (i = 0; i < count; ++i)
	{
		t=insert_node(t,arr[i]);
	}
	
	//打印二叉树深度
	printf("tree depth:%d\n", depth(t));

	//判断是否为AVL树
	printf("AVL tree:%d\n",is_avl(t));

	//最小节点
	// np=min_node(t);
	// printf("%p:%d\n", np,np->element);
	
	//最大节点
	// np=max_node(t);
	// printf("%p:%d\n", np,np->element);

	//查找节点
	//删除节点
	delete_node(find_node(t,1));
	delete_node(find_node(t,3));
	//delete_node(find_node(t,7));

	printf("%d\n", count_node(t));
	exit(0);
	if(!is_avl(t)){
		printf("不是AVL树，重置\n");
		reset_avl(t);
	}

	//反转二叉树
	
	//打印二叉树所有节点
	
	//打印二叉树节点总数
	//printf("node count:%d\n", count_node(t));

	//打印二叉树深度
	printf("tree depth:%d\n", depth(t));

	//判断是否为AVL树
	printf("AVL tree:%d\n",is_avl(t));
}

/**
 * 插入节点
 * @param  t   [description]
 * @param  num [description]
 * @return     [description]
 */
tree insert_node(tree t,int num){
	position np;
	//分配一个结构类型大小内存空间，如果是position 是指该结构类型的指针类型内存空间
	//会出现内存泄露np=(position) malloc(sizeof(position));
	np=(position) malloc(sizeof(struct node));

	np->element=num;
	np->p_index=NULL;
	np->l_index=NULL;
	np->r_index=NULL;

	if(t==NULL){
		t=np;
	}else{
		insert_node_nempty(t,np);
	}

	return t;
}

/**
 * 构建二叉树，并且构建AVL树
 * @param t  [description]
 * @param np [description]
 */
void insert_node_nempty(tree t,position np){
	//判断父节点是否存在，并且只有一个节点,则需要重置AVL特性
	position parent=t->p_index;
	if(parent!=NULL&&(parent->l_index==NULL||parent->r_index==NULL)){
		swap_avl(t,parent,np);
	}else{
		if(np->element<=t->element){
			if(t->l_index==NULL){
				np->p_index=t;
				t->l_index=np;
				return;
			}else{
				insert_node_nempty(t->l_index,np);
			}
		}else{//大于当前节点，则为右子节点
			if(t->r_index==NULL){
				np->p_index=t;
				t->r_index=np;
				return;
			}else{
				insert_node_nempty(t->r_index,np);
			}
		}
	}

	//普通二叉树节点添加
	//小于当前节点放左边
	// if(np->element<=t->element){
	// 	if(t->l_index==NULL){
	// 		np->p_index=t;
	// 		t->l_index=np;
	// 		return;
	// 	}else{
	// 		insert_node_nempty(t->l_index,np);
	// 	}
	// }else{//大于当前节点，则为右子节点
	// 	if(t->r_index==NULL){
	// 		np->p_index=t;
	// 		t->r_index=np;
	// 		return;
	// 	}else{
	// 		insert_node_nempty(t->r_index,np);
	// 	}
	// }
}

/**
 * 交换树节点以完成AVL树构建
 * @param t      [description]
 * @param parent [description]
 * @param np     [description]
 */
void swap_avl(tree t,position parent,position np){
	elementTp p_element=parent->element;
	if(parent->l_index==NULL){//左节点为空
		if(np->element>t->element){//逆向转动一次
			parent->element=t->element;
			t->element=np->element;
			np->element=p_element;
		}else{//要转动两次
			parent->element=np->element;
			np->element=p_element;
		}

		parent->l_index=np;
	}else{
		if(np->element>t->element){
			parent->element=np->element;
			np->element=p_element;
		}else{
			parent->element=t->element;
			t->element=np->element;
			np->element=p_element;
		}

		parent->r_index=np;
	}
}

/**
 * 查找节点
 * @param  t   [description]
 * @param  num [description]
 * @return     [description]
 */
position find_node(tree t,int num){
	position np;
	np=t;

	while(np!=NULL){
		if(np->element==num){
			return np;
		}

		if(np->element<num){
			np=np->r_index;
		}else{
			np=np->l_index;
		}
	}

	return NULL;
}

/**
 * 最小节点
 * @param  t [description]
 * @return   [description]
 */
position min_node(tree t){
	position np;
	np=t;

	while(np->l_index!=NULL){
		np=np->l_index;
	}

	return np;
}

/**
 * 最大节点
 * @param  t [description]
 * @return   [description]
 */
position max_node(tree t){
	position np;
	np=t;

	while(np->r_index!=NULL){
		np=np->r_index;
	}

	return np;
}

/**
 * 删除节点
 * @param t  [description]
 * @param np [description]
 */
int delete_node(position np){
	position replace;
	int element;

	if(is_leaf(np)){//如果是叶节点，删除即可
	        return delete_leaf(np);

	}else{//如果不是叶节点，把值替换以下,指针方面不需要任何处理,第归下去
		//找到左边最大的节点，或者右边最小的节点
		replace=(np->l_index!=NULL)?max_node(np->l_index):min_node(np->r_index);
		element=np->element;
		np->element=delete_node(replace);
		return element;
	}
}

/**
 * 重置AVL树
 * @param t [description]
 */
void reset_avl(tree t){
	position parent=t->p_index;
	if(parent!=NULL&&(parent->l_index==NULL||parent->r_index==NULL)){
		if(t->l_index!=NULL)
			swap_avl(t,parent,t->l_index);

		if(t->r_index!=NULL)
			swap_avl(t,parent,t->r_index);
	}

	if(t->l_index!=NULL){
		reset_avl(t->l_index);
	}

	if(t->r_index!=NULL){
		reset_avl(t->r_index);
	}
}

/**
 * 删除叶节点
 * @param np [description]
 */
static int delete_leaf(position np){
	position parent;
	parent=np->p_index;
	int element=np->element;

	if(!is_root(np)){
		if(parent->l_index=np){
			parent->l_index=NULL;
		}else{
			parent->r_index=NULL;
		}
	}

	free(np);

	return element;
}

/**
 * 是不是叶节点
 * @param  np [description]
 * @return    [description]
 */
static int is_leaf(position np){
	return (np->l_index==NULL&&np->r_index==NULL);
}

/**
 * 是否是根节点
 * @param  np [description]
 * @return    [description]
 */
static int is_root(position np){
	return (np->p_index==NULL);
}

/**
 * 返回节点总数
 * @param  t [description]
 * @return   [description]
 */
int count_node(tree t){
	int count=1,l_count=0,r_count=0;

	if(t->l_index!=NULL){
		l_count=count_node(t->l_index);
	}

	if(t->r_index!=NULL){
		r_count=count_node(t->r_index);
	}

	return count+l_count+r_count;
}

/**
 * 打印深度,构建AVL树保证深度最大为floor(log^n+1)
 * @param  t [description]
 * @return   [description]
 */
int depth(tree t){
	if(t==NULL)
		return 0;

	int t_depth=0,l_depth=0,r_depth=0,c_depth=0;
	if(t->l_index!=NULL||t->r_index!=NULL){
		t_depth++;

		if(t->l_index!=NULL){
			l_depth=depth(t->l_index);
		}

		if(t->r_index!=NULL){
			r_depth=depth(t->r_index);
		}

		c_depth=(l_depth>r_depth)?l_depth:r_depth;
	}else{
		t_depth++;//叶节点、或者一个根结点
	}


	return t_depth+c_depth;
}

/**
 * 是否为AVL树
 * @param  t [description]
 * @return   [description]
 */
int is_avl(tree t){
	int t_depth=(int)floor((log((float)count_node(t))/log(2)+1));
	return (t_depth==depth(t));
}